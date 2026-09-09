<?php

define('ABSPATH', dirname(__DIR__));

function add_action()
{
}

require __DIR__ . '/test-helpers.php';
require dirname(__DIR__) . '/includes/core/submission-preview.php';

$valid_input = [
    'title' => 'Titel',
    'content' => 'Inhalt',
    'tags' => 'Verein',
    'category_id' => 3,
    'ki_active' => false,
    'ki_tags_active' => false,
    'style_group' => '',
];

beitrag_test_assert_same([], beitragseinreichung_validate_submission_input($valid_input), 'Gueltige Eingaben werden abgelehnt.');

$invalid_input = $valid_input;
$invalid_input['title'] = '';
$invalid_input['content'] = '';
$invalid_input['tags'] = '';
$invalid_input['category_id'] = 0;
$invalid_input['ki_active'] = true;

beitrag_test_assert_same(5, count(beitragseinreichung_validate_submission_input($invalid_input)), 'Nicht alle Pflichtfelder werden serverseitig erkannt.');

$ai_tags_input = $valid_input;
$ai_tags_input['tags'] = '';
$ai_tags_input['ki_active'] = true;
$ai_tags_input['ki_tags_active'] = true;
$ai_tags_input['style_group'] = 'Vereinsleben';

beitrag_test_assert_same([], beitragseinreichung_validate_submission_input($ai_tags_input), 'KI-Schlagwoerter duerfen die manuelle Eingabe ersetzen.');

echo "Einreichungsvalidierungs-Smoke-Test erfolgreich.\n";
