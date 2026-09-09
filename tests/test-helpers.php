<?php

/**
 * Bricht einen Entwicklungstest bei einer falschen Bedingung ab.
 */
function beitrag_test_assert($condition, $message)
{
    if ($condition) {
        return;
    }

    fwrite(STDERR, "Fehler: {$message}\n");
    exit(1);
}

/**
 * Vergleicht zwei Werte strikt.
 */
function beitrag_test_assert_same($expected, $actual, $message)
{
    if ($expected === $actual) {
        return;
    }

    fwrite(STDERR, "Fehler: {$message}\nErwartet: " . var_export($expected, true) . "\nErhalten: " . var_export($actual, true) . "\n");
    exit(1);
}
