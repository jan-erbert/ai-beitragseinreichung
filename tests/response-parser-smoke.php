<?php

define('ABSPATH', dirname(__DIR__));

require __DIR__ . '/test-helpers.php';
require dirname(__DIR__) . '/includes/ai/response-parser.php';

$expected = ['title' => 'Test', 'content' => 'Inhalt'];

beitrag_test_assert_same($expected, beitrag_ki_parse_json_antwort('{"title":"Test","content":"Inhalt"}'), 'Direktes JSON wird nicht gelesen.');
beitrag_test_assert_same($expected, beitrag_ki_parse_json_antwort("```json\n{\"title\":\"Test\",\"content\":\"Inhalt\"}\n```"), 'JSON-Codeblock wird nicht gelesen.');
beitrag_test_assert_same($expected, beitrag_ki_parse_json_antwort('Antwort: {"title":"Test","content":"Inhalt"} Ende'), 'Eingebettetes JSON wird nicht gelesen.');
beitrag_test_assert_same(null, beitrag_ki_parse_json_antwort('kein JSON'), 'Ungueltige Antwort muss null liefern.');

echo "KI-Response-Parser-Smoke-Test erfolgreich.\n";
