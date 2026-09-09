<?php

define('ABSPATH', dirname(__DIR__));

$beitrag_test_options = [
    'beitragseinreichung_ki_log_limit' => 2,
    'beitragseinreichung_ki_logs' => [['titel' => 'Alt'], ['titel' => 'Mitte'], ['titel' => 'Neu']],
];

function add_action()
{
}

function get_option($key, $default = false)
{
    global $beitrag_test_options;

    return array_key_exists($key, $beitrag_test_options) ? $beitrag_test_options[$key] : $default;
}

function update_option($key, $value, $autoload = null)
{
    global $beitrag_test_options;
    $beitrag_test_options[$key] = $value;

    return true;
}

function current_time()
{
    return '2026-09-09 12:00:00';
}

function get_the_title()
{
    return 'Neuster Beitrag';
}

function get_post_field($field)
{
    return $field === 'post_excerpt' ? 'Auszug' : '';
}

require __DIR__ . '/test-helpers.php';
require dirname(__DIR__) . '/includes/ai/ai-logging.php';

beitrag_test_assert_same(2, beitragseinreichung_get_ai_log_limit(), 'Gespeicherte Protokollgrenze wird nicht gelesen.');
beitragseinreichung_trim_ai_logs();
beitrag_test_assert_same(
    [['titel' => 'Mitte'], ['titel' => 'Neu']],
    get_option('beitragseinreichung_ki_logs'),
    'Beim Kuerzen bleiben nicht die neuesten Protokolleintraege erhalten.'
);

beitrag_ki_log_speichern(1, 2, 'Original', 'Optimiert', 'Alt', 'Neu', 'gpt-5.6-terra', '', 'Standard');
beitrag_test_assert_same(2, count(get_option('beitragseinreichung_ki_logs')), 'Neue Protokolleintraege beachten die Obergrenze nicht.');
beitrag_test_assert_same('Neuster Beitrag', get_option('beitragseinreichung_ki_logs')[1]['titel'], 'Der neueste Protokolleintrag wurde nicht behalten.');

$beitrag_test_options['beitragseinreichung_ki_log_limit'] = 900;
beitrag_test_assert_same(500, beitragseinreichung_get_ai_log_limit(), 'Die maximale Protokollgrenze muss 500 sein.');
$beitrag_test_options['beitragseinreichung_ki_log_limit'] = 0;
beitrag_test_assert_same(1, beitragseinreichung_get_ai_log_limit(), 'Die minimale Protokollgrenze muss 1 sein.');

echo "KI-Protokoll-Smoke-Test erfolgreich.\n";
