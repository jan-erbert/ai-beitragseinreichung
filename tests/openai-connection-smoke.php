<?php

define('ABSPATH', dirname(__DIR__));

$beitrag_test_options = [
    'beitragseinreichung_api_key' => '',
    'beitragseinreichung_ki_aktiv' => 1,
    'beitragseinreichung_ki_modell' => 'gpt-5.6-terra',
];

class Beitrag_Test_Wp_Error
{
    public function get_error_message()
    {
        return 'Test-Netzwerkfehler';
    }
}

function get_option($key, $default = false)
{
    global $beitrag_test_options;

    return array_key_exists($key, $beitrag_test_options) ? $beitrag_test_options[$key] : $default;
}

function update_option($key, $value)
{
    global $beitrag_test_options;
    $beitrag_test_options[$key] = $value;

    return true;
}

function current_time()
{
    return '2026-09-09 12:00:00';
}

function wp_json_encode($value)
{
    return json_encode($value);
}

function wp_remote_post()
{
    return new Beitrag_Test_Wp_Error();
}

function is_wp_error($value)
{
    return $value instanceof Beitrag_Test_Wp_Error;
}

require __DIR__ . '/test-helpers.php';
require dirname(__DIR__) . '/includes/ai/ai-models.php';
require dirname(__DIR__) . '/includes/ai/openai-client.php';

$no_key_status = beitragseinreichung_test_openai_verbindung();
beitrag_test_assert_same('kein_key', $no_key_status['status'], 'Ein fehlender API-Key wird nicht erkannt.');
beitrag_test_assert_same(1, get_option('beitragseinreichung_ki_aktiv'), 'Ein fehlender API-Key darf die KI nicht automatisch deaktivieren.');

$beitrag_test_options['beitragseinreichung_api_key'] = 'test-key';
$network_status = beitragseinreichung_test_openai_verbindung();
beitrag_test_assert_same('netzwerkfehler', $network_status['status'], 'Ein Netzwerkfehler wird nicht erkannt.');
beitrag_test_assert_same(1, get_option('beitragseinreichung_ki_aktiv'), 'Ein Netzwerkfehler darf die KI nicht automatisch deaktivieren.');

echo "OpenAI-Verbindungstest-Smoke-Test erfolgreich.\n";
