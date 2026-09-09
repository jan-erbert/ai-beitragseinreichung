<?php

define('ABSPATH', dirname(__DIR__));

function sanitize_text_field($value)
{
    return trim(strip_tags((string) $value));
}

function wp_unslash($value)
{
    return $value;
}

require __DIR__ . '/test-helpers.php';
require dirname(__DIR__) . '/includes/core/tags.php';

beitrag_test_assert_same(
    ['Nieder-Olm', 'Köln', 'Straßenlauf'],
    beitragseinreichung_parse_tags(' Nieder-Olm, nieder-olm, Köln, KÖLN, Straßenlauf '),
    'Schlagwoerter werden nicht stabil normalisiert und dedupliziert.'
);
beitrag_test_assert_same(
    ['2026', 'Laufen', 'Nieder-Olm'],
    beitragseinreichung_merge_tags(['2026', 'Laufen'], 'laufen, Nieder-Olm'),
    'Mehrere Schlagwortlisten werden nicht korrekt zusammengefuehrt.'
);

echo "Schlagwort-Smoke-Test erfolgreich.\n";
