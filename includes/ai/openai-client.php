<?php

defined('ABSPATH') || exit;

/**
 * Ruft die OpenAI Responses API auf.
 */
function beitrag_openai_responses_request($api_key, $request_body, $timeout = 90)
{
    return wp_remote_post('https://api.openai.com/v1/responses', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => wp_json_encode($request_body),
        'timeout' => $timeout,
    ]);
}

/**
 * Liest den Text aus einer Responses-API-Antwort aus.
 */
function beitrag_openai_extract_response_text($body)
{
    if (!is_array($body)) {
        return '';
    }

    if (isset($body['output_text']) && is_string($body['output_text'])) {
        return $body['output_text'];
    }

    foreach (($body['output'] ?? []) as $output_item) {
        foreach (($output_item['content'] ?? []) as $content_item) {
            if (isset($content_item['text']) && is_string($content_item['text'])) {
                return $content_item['text'];
            }
        }
    }

    return '';
}

/**
 * Liest eine Fehlermeldung aus einer OpenAI-Antwort aus.
 */
function beitrag_openai_extract_error_message($body, $fallback = 'Unbekannter OpenAI-Fehler.')
{
    if (!is_array($body)) {
        return $fallback;
    }

    if (!empty($body['error']['message'])) {
        return (string) $body['error']['message'];
    }

    if (!empty($body['message'])) {
        return (string) $body['message'];
    }

    return $fallback;
}

/**
 * Liefert das JSON-Schema fuer optimierte Beitraege.
 */
function beitrag_ki_get_optimized_post_schema()
{
    return [
        'type' => 'object',
        'additionalProperties' => false,
        'properties' => [
            'title' => [
                'type' => 'string',
                'description' => 'Ein kurzer WordPress-Beitragstitel ohne Markdown oder HTML.',
            ],
            'content' => [
                'type' => 'string',
                'description' => 'Der optimierte Beitragstext.',
            ],
            'excerpt' => [
                'type' => 'string',
                'description' => 'Optionaler Textauszug oder leerer String.',
            ],
        ],
        'required' => ['title', 'content', 'excerpt'],
    ];
}

/**
 * Optimiert Beitragstitel, Inhalt und optional Textauszug in einem KI-Aufruf.
 */
function beitrag_ki_optimiere_beitrag($titel, $inhalt, $modell, $zusatz, $stilgruppe_label, $excerpt_auto = false)
{
    global $beitrag_ki_fehler, $beitrag_ki_fehler_meldung;

    $modell = beitrag_normalize_ai_model($modell);
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('beitragseinreichung_api_key');

    if (!$api_key || trim($titel . $inhalt) === '') {
        $beitrag_ki_fehler = true;
        $beitrag_ki_fehler_meldung = !$api_key ? 'Kein API-Key hinterlegt.' : 'Titel oder Inhalt ist leer.';
        return [
            'title' => $titel,
            'content' => $inhalt,
            'excerpt' => '',
        ];
    }

    $stil = beitrag_ki_ermittle_stil_prompt($stilgruppe_label);
    $prompt = beitrag_ki_baue_beitrag_prompt($titel, $inhalt, $stil, $zusatz, $excerpt_auto);

    if (strlen($prompt) > 50000) {
        $beitrag_ki_fehler = true;
        $beitrag_ki_fehler_meldung = 'Der Text ist fuer die KI-Vorschau zu lang. Bitte kuerze den Beitrag oder die Zusatzhinweise.';

        return [
            'title' => $titel,
            'content' => $inhalt,
            'excerpt' => '',
        ];
    }

    $request_body = array_merge([
        'model' => $modell,
        'input' => [
            [
                'role' => 'system',
                'content' => 'Du bist ein redaktioneller WordPress-Assistent. Antworte exakt im geforderten JSON-Schema.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'text' => [
            'format' => [
                'type' => 'json_schema',
                'name' => 'optimized_post',
                'strict' => true,
                'schema' => beitrag_ki_get_optimized_post_schema(),
            ],
        ],
        'max_output_tokens' => 3000,
    ], beitrag_get_ai_model_request_options($modell));

    $response = beitrag_openai_responses_request($api_key, $request_body);

    if (is_wp_error($response)) {
        $beitrag_ki_fehler = true;
        $beitrag_ki_fehler_meldung = $response->get_error_message();
        error_log('OpenAI Fehler: ' . $response->get_error_message());
        beitrag_ki_admin_benachrichtigen('Fehler: ' . $response->get_error_message());

        return [
            'title' => $titel,
            'content' => $inhalt,
            'excerpt' => '',
        ];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        $beitrag_ki_fehler = true;
        $fehlermeldung = beitrag_openai_extract_error_message($body, 'OpenAI-Fehlercode: ' . $code);
        $beitrag_ki_fehler_meldung = $fehlermeldung;
        beitrag_ki_admin_benachrichtigen($fehlermeldung);

        return [
            'title' => $titel,
            'content' => $inhalt,
            'excerpt' => '',
        ];
    }

    $content = beitrag_openai_extract_response_text($body);
    $data = beitrag_ki_parse_json_antwort($content);

    if (!$data || empty($data['title']) || empty($data['content'])) {
        $beitrag_ki_fehler = true;
        $beitrag_ki_fehler_meldung = 'Strukturierte KI-Antwort war unvollstaendig oder ungueltig.';
        beitrag_ki_admin_benachrichtigen('Strukturierte KI-Antwort war unvollstaendig oder ungueltig.');

        return [
            'title' => $titel,
            'content' => $inhalt,
            'excerpt' => '',
        ];
    }

    return [
        'title' => sanitize_text_field((string) $data['title']),
        'content' => wp_kses_post((string) $data['content']),
        'excerpt' => isset($data['excerpt']) ? sanitize_text_field((string) $data['excerpt']) : '',
    ];
}

/**
 * Verbessert Text ueber die OpenAI-API.
 */
function beitrag_ki_verbessere_text($text, $ziel = 'Beitragstitel oder Inhalt', $modell = null, $zusatz = '')
{
    global $beitrag_ki_fehler; // NEU

    $modell = beitrag_normalize_ai_model($modell);

    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('beitragseinreichung_api_key');
    if (!$api_key || empty(trim($text))) {
        $beitrag_ki_fehler = true; // Fehler, kein API-Key oder leerer Text
        return $text;
    }

    $stil = beitrag_ki_ermittle_stil_prompt('');


    if (stripos($ziel, 'Beitragstitel') !== false) {
        $system_message = "Du formulierst kurze WordPress-Beitragstitel. Gib nur einen einzelnen Titel zurueck.";
        $prompt = <<<EOT
        Formuliere aus dem folgenden Titel einen kurzen, klaren WordPress-Beitragstitel.
        Regeln:
        - Maximal 90 Zeichen.
        - Nur ein einzelner Titel, kein Untertitel, kein Absatz.
        - Keine Markdown-Formatierung, keine Sternchen, keine HTML-Tags.
        - Emojis sind erlaubt, wenn sie zur Stilgruppe passen, aber maximal 1-2.
        - Keine neuen Fakten erfinden.
        - Keine Details aus dem Beitragstext hinzufuegen, die nicht im Titel stehen.
        Stil: $stil

        Titel:
        $text
        EOT;
    } elseif (stripos($ziel, 'Textauszug') !== false) {
        $system_message = "Du bist ein professioneller Textoptimierer. Erfinde niemals Inhalte hinzu. Gib nur reale Zusammenfassungen basierend auf dem übergebenen Text zurück.";
        $prompt = <<<EOT
        Bitte fasse den folgenden optimierten Blogbeitrag in 1–2 spannenden, kurzen Sätzen zusammen. 
        Hebe die interessantesten Punkte hervor. 
        Sei stilistisch ansprechend, aber **füge nichts hinzu**, was nicht im Originaltext steht.
        Verwende hier bitte keinerlei Formatierungen: **kein Markdown, kein Fettdruck, keine Sonderzeichen, keine Emojis** – nur reiner Fließtext.
        Stil: $stil

        Hier der optimierte Beitrag:
        $text
        EOT;
    } else {
        $system_message = "Du bist ein professioneller Textoptimierer für Blogbeiträge. Gib nur den gewünschten Text zurück, ohne Erklärungen oder Kommentare.";
        $prompt = <<<EOT
        Bitte überarbeite den folgenden $ziel sprachlich und stilistisch. Gib **nur den überarbeiteten Text** zurück – ohne zusätzliche Hinweise, ohne Formatierungen, ohne Gutenberg-Kommentare.
        Der Text soll so zurückgegeben werden, dass er sich gut für einen redaktionellen WordPress-Beitrag eignet. Erfinde dabei keine Inhalte.

        Stil: $stil

        Text:
        $text
        EOT;
    }

    if (!empty($zusatz)) {
        $prompt .= "\n\nZusätzliche Hinweise: $zusatz";
    }

    $request_body = array_merge([
        'model' => $modell,
        'input' => [
            [
                'role' => 'system',
                'content' => $system_message,
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'max_output_tokens' => 2048,
    ], beitrag_get_ai_model_request_options($modell));

    $response = beitrag_openai_responses_request($api_key, $request_body);

    if (is_wp_error($response)) {
        $beitrag_ki_fehler = true;
        error_log('OpenAI Fehler: ' . $response->get_error_message());

        // Admin benachrichtigen
        beitrag_ki_admin_benachrichtigen('Fehler: ' . $response->get_error_message());

        return $text;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        $beitrag_ki_fehler = true;
        $fehlermeldung = beitrag_openai_extract_error_message($body, 'OpenAI-Fehlercode: ' . $code);
        beitrag_ki_admin_benachrichtigen($fehlermeldung);

        return $text;
    }

    $content = beitrag_openai_extract_response_text($body);
    if ($content === '') {
        $beitrag_ki_fehler = true;

        // Admin benachrichtigen
        beitrag_ki_admin_benachrichtigen('Antwort unvollständig oder ungültig.');

        return $text;
    }

    return $content;
}

/**
 * Testet die Verbindung zur OpenAI-API.
 */
function beitragseinreichung_test_openai_verbindung($api_key = null)
{
    if (!$api_key) {
        $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('beitragseinreichung_api_key');
    }

    $modell = beitrag_normalize_ai_model(get_option('beitragseinreichung_ki_modell', beitrag_get_default_ai_model()));

    $status = [
        'zeit' => current_time('mysql'),
    ];

    if (!$api_key || trim($api_key) === '') {
        $status['status'] = 'kein_key';
        $status['info'] = 'Kein API-Key hinterlegt.';
        update_option('beitragseinreichung_ki_aktiv', 0); // KI deaktivieren
        update_option('beitragseinreichung_api_status', $status);
        return $status;
    }

    $response = beitrag_openai_responses_request($api_key, array_merge([
        'model' => $modell,
        'input' => [
            [
                'role' => 'user',
                'content' => 'Sag nur: OK',
            ],
        ],
        'max_output_tokens' => 16,
    ], beitrag_get_ai_model_request_options($modell)), 20);

    if (is_wp_error($response)) {
        $status['status'] = 'netzwerkfehler';
        $status['info'] = $response->get_error_message();
        update_option('beitragseinreichung_ki_aktiv', 0); // KI deaktivieren bei Fehler
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = beitrag_openai_extract_response_text($body);

        if ($code === 200 && $content !== '') {
            $status['status'] = 'erfolgreich';
            $status['info'] = 'Verbindung erfolgreich.';
            // Nur bei Erfolg keine Änderung am Aktiv-Status
        } else {
            $status['status'] = 'fehler';
            $status['info'] = beitrag_openai_extract_error_message($body, 'Fehlercode: ' . $code);
            update_option('beitragseinreichung_ki_aktiv', 0); // Deaktivieren bei Fehler
        }
    }

    update_option('beitragseinreichung_api_status', $status);
    return $status;
}
