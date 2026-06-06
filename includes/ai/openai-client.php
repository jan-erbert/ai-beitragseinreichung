<?php

defined('ABSPATH') || exit;

/**
 * Optimiert Beitragstitel, Inhalt und optional Textauszug in einem KI-Aufruf.
 */
function beitrag_ki_optimiere_beitrag($titel, $inhalt, $modell, $zusatz, $stilgruppe_label, $excerpt_auto = false)
{
    global $beitrag_ki_fehler;

    $modell = beitrag_normalize_ai_model($modell);
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('beitragseinreichung_api_key');

    if (!$api_key || trim($titel . $inhalt) === '') {
        $beitrag_ki_fehler = true;
        return [
            'title' => $titel,
            'content' => $inhalt,
            'excerpt' => '',
        ];
    }

    $stil = beitrag_ki_ermittle_stil_prompt($stilgruppe_label);
    $prompt = beitrag_ki_baue_beitrag_prompt($titel, $inhalt, $stil, $zusatz, $excerpt_auto);

    $request_body = wp_json_encode([
        'model' => $modell,
        'messages' => [
            ['role' => 'system', 'content' => 'Du bist ein redaktioneller WordPress-Assistent. Antworte nur mit validem JSON.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'max_tokens' => 3000,
        'response_format' => ['type' => 'json_object'],
    ]);

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $request_body,
        'timeout' => 90,
    ]);

    if (is_wp_error($response)) {
        $beitrag_ki_fehler = true;
        error_log('OpenAI Fehler: ' . $response->get_error_message());
        beitrag_ki_admin_benachrichtigen('Fehler: ' . $response->get_error_message());

        return [
            'title' => $titel,
            'content' => $inhalt,
            'excerpt' => '',
        ];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    $data = beitrag_ki_parse_json_antwort($content);

    if (!$data || empty($data['title']) || empty($data['content'])) {
        $beitrag_ki_fehler = true;
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

    $stilgruppe_label = !empty($_POST['beitrag_ki_stilgruppe']) ? sanitize_text_field($_POST['beitrag_ki_stilgruppe']) : '';
    $stil = beitrag_ki_ermittle_stil_prompt($stilgruppe_label);


    if (stripos($ziel, 'Beitragstitel') !== false) {
        $temperature = 0.3;
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
        $temperature = 0.4;
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
        $temperature = 0.7;
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

    $request_body = json_encode([
        'model' => $modell,
        'messages' => [
            ['role' => 'system', 'content' => $system_message],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => $temperature,
        'max_tokens' => 2048,
    ]);

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $request_body,
        'timeout' => 90,
    ]);

    if (is_wp_error($response)) {
        $beitrag_ki_fehler = true;
        error_log('OpenAI Fehler: ' . $response->get_error_message());

        // Admin benachrichtigen
        beitrag_ki_admin_benachrichtigen('Fehler: ' . $response->get_error_message());

        return $text;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($body['choices'][0]['message']['content'])) {
        $beitrag_ki_fehler = true;

        // Admin benachrichtigen
        beitrag_ki_admin_benachrichtigen('Antwort unvollständig oder ungültig.');

        return $text;
    }

    return $body['choices'][0]['message']['content'];
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

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => $modell,
            'messages' => [
                ['role' => 'user', 'content' => 'Sag nur: ✅'],
            ],
            'max_tokens' => 5,
        ]),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        $status['status'] = 'netzwerkfehler';
        $status['info'] = $response->get_error_message();
        update_option('beitragseinreichung_ki_aktiv', 0); // KI deaktivieren bei Fehler
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && isset($body['choices'][0]['message']['content'])) {
            $status['status'] = 'erfolgreich';
            $status['info'] = 'Verbindung erfolgreich.';
            // Nur bei Erfolg keine Änderung am Aktiv-Status
        } else {
            $status['status'] = 'fehler';
            $status['info'] = 'Fehlercode: ' . $code;
            update_option('beitragseinreichung_ki_aktiv', 0); // Deaktivieren bei Fehler
        }
    }

    update_option('beitragseinreichung_api_status', $status);
    return $status;
}
