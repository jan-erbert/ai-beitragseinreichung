<?php

define('ABSPATH', dirname(__DIR__));

function esc_html($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function esc_url($url)
{
    return filter_var((string) $url, FILTER_VALIDATE_URL) ? (string) $url : '';
}

function wp_kses_post($text)
{
    return (string) $text;
}

function get_bloginfo($key)
{
    return $key === 'charset' ? 'UTF-8' : '';
}

function wp_strip_all_tags($text)
{
    return strip_tags((string) $text);
}

function wp_html_excerpt($text, $count, $more = '')
{
    return mb_substr((string) $text, 0, (int) $count) . $more;
}

require dirname(__DIR__) . '/includes/formatting/gutenberg-formatting.php';

function assert_contains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, "Fehler: {$message}\nGesucht: {$needle}\n");
        exit(1);
    }
}

$result_text = <<<MD
Beim wiederaufgelebten Straßenfestlauf in **Nieder-Olm** zeigten wir starke Leistungen.

## 10 km
– Toufeek Al Suliman – **44:56 min (5. Platz M30)**
– Andreas Kramarz – **46:57 min (3. Platz M50)**

## 5 km
– Jana Eisenbrandt – **27:57 min (1. Platz W35)**
MD;

$result_blocks = beitrag_wandle_zu_gutenberg_blocks($result_text);

assert_contains('<!-- wp:heading {"level":2} -->', $result_blocks, 'Markdown-Ueberschrift wurde nicht als Gutenberg-Heading gerendert.');
assert_contains('<h2>10 km</h2>', $result_blocks, '10-km-Ueberschrift fehlt.');
assert_contains('<!-- wp:list -->', $result_blocks, 'Ergebniszeilen wurden nicht als Liste gerendert.');
assert_contains('<strong>44:56 min (5. Platz M30)</strong>', $result_blocks, 'Fettdruck in Ergebnisliste fehlt.');

$mixed_text = <<<MD
# Titel

Absatz mit **fett**, __auch fett__, *kursiv*, _auch kursiv_, `Code` und [Link](https://example.org).

1. Erstens
2. Zweitens

> Wichtiges Zitat
> zweite Zeile

---

| Name | Zeit | Platz |
|---|---:|---|
| Toufeek | **44:56 min** | 5. M30 |
| Jana | 27:57 min | 1. W35 |
MD;

$mixed_blocks = beitrag_wandle_zu_gutenberg_blocks($mixed_text);

assert_contains('<!-- wp:heading {"level":1} -->', $mixed_blocks, 'H1-Ueberschrift wurde nicht gerendert.');
assert_contains('<strong>auch fett</strong>', $mixed_blocks, 'Unterstrich-Fettdruck fehlt.');
assert_contains('<em>auch kursiv</em>', $mixed_blocks, 'Unterstrich-Kursivschrift fehlt.');
assert_contains('<code>Code</code>', $mixed_blocks, 'Inline-Code fehlt.');
assert_contains('<a href="https://example.org" target="_blank" rel="noopener noreferrer">Link</a>', $mixed_blocks, 'Markdown-Link fehlt.');
assert_contains('<!-- wp:list {"ordered":true} -->', $mixed_blocks, 'Nummerierte Liste wurde nicht gerendert.');
assert_contains('<!-- wp:quote -->', $mixed_blocks, 'Zitat wurde nicht gerendert.');
assert_contains('<!-- wp:separator -->', $mixed_blocks, 'Trenner wurde nicht gerendert.');
assert_contains('<!-- wp:table -->', $mixed_blocks, 'Markdown-Tabelle wurde nicht gerendert.');
assert_contains('<th>Name</th>', $mixed_blocks, 'Tabellenkopf fehlt.');
assert_contains('<td><strong>44:56 min</strong></td>', $mixed_blocks, 'Inline-Formatierung in Tabelle fehlt.');

echo "Markdown-Formatting-Smoke-Test erfolgreich.\n";
