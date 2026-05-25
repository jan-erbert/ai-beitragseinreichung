<?php

defined('ABSPATH') || exit;

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

    $grundstil = get_option('beitragseinreichung_ki_stil', '');
    $stilgruppe_label = !empty($_POST['beitrag_ki_stilgruppe']) ? sanitize_text_field($_POST['beitrag_ki_stilgruppe']) : '';
    $stilgruppen = get_option('beitragseinreichung_ki_stilgruppen', []);
    $stilbeschreibung = '';

    // passende Stilbeschreibung auslesen
    foreach ($stilgruppen as $gruppe) {
        if (isset($gruppe['label']) && $gruppe['label'] === $stilgruppe_label) {
            $stilbeschreibung = trim($gruppe['stil'] ?? '');
            break;
        }
    }

    // Prompt-Stil zusammensetzen
    $stil = trim($stilbeschreibung . ($grundstil ? "\n\n" . $grundstil : ''));


    if (stripos($ziel, 'Textauszug') !== false) {
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
            ['role' => 'system', 'content' => "Du bist ein professioneller Textoptimierer für Blogbeiträge. Gib nur den gewünschten Text zurück, ohne Erklärungen oder Kommentare."],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
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

    $beitrag_ki_fehler = false; // Alles gut
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
