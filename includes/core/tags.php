<?php

defined('ABSPATH') || exit;

/**
 * Normalisiert ein Schlagwort fuer Vergleich und Speicherung.
 */
function beitragseinreichung_normalize_tag($tag)
{
    $tag = sanitize_text_field((string) $tag);
    $tag = preg_replace('/\s+/', ' ', $tag);

    return trim($tag, " \t\n\r\0\x0B,;");
}

/**
 * Liefert einen Vergleichsschluessel fuer Schlagwort-Deduplizierung.
 */
function beitragseinreichung_get_tag_key($tag)
{
    $tag = beitragseinreichung_normalize_tag($tag);

    return function_exists('mb_strtolower') ? mb_strtolower($tag) : strtolower($tag);
}

/**
 * Wandelt eine kommagetrennte Schlagwortliste oder ein Array in saubere Tags.
 *
 * @param mixed $value Schlagwortliste.
 * @return string[]
 */
function beitragseinreichung_parse_tags($value)
{
    $items = is_array($value) ? $value : explode(',', (string) $value);
    $tags = [];

    foreach ($items as $item) {
        $tag = beitragseinreichung_normalize_tag(wp_unslash($item));
        if ($tag === '') {
            continue;
        }

        $key = beitragseinreichung_get_tag_key($tag);
        if (!isset($tags[$key])) {
            $tags[$key] = $tag;
        }
    }

    return array_values($tags);
}

/**
 * Fuehrt mehrere Schlagwortlisten zusammen und entfernt Duplikate.
 *
 * @param mixed ...$tag_lists Schlagwortlisten.
 * @return string[]
 */
function beitragseinreichung_merge_tags(...$tag_lists)
{
    $merged = [];

    foreach ($tag_lists as $tag_list) {
        foreach (beitragseinreichung_parse_tags($tag_list) as $tag) {
            $key = beitragseinreichung_get_tag_key($tag);
            if (!isset($merged[$key])) {
                $merged[$key] = $tag;
            }
        }
    }

    return array_values($merged);
}

/**
 * Liefert das aktuelle Jahr als Schlagwort.
 */
function beitragseinreichung_get_current_year_tag()
{
    return current_time('Y');
}

/**
 * Liefert automatisch vorgeschlagene Schlagwoerter.
 *
 * @return string[]
 */
function beitragseinreichung_get_default_tags()
{
    if (!get_option('beitragseinreichung_tags_jahr_aktiv')) {
        return [];
    }

    return [beitragseinreichung_get_current_year_tag()];
}

/**
 * Liefert haeufig verwendete WordPress-Schlagwoerter mit Nutzungszahl.
 *
 * @return array<int, array{name: string, count: int}>
 */
function beitragseinreichung_get_frequent_tags_with_counts($limit = 50)
{
    $terms = get_terms([
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
        'orderby' => 'count',
        'order' => 'DESC',
        'number' => (int) $limit,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    return array_map(static function ($term) {
        return [
            'name' => $term->name,
            'count' => (int) $term->count,
        ];
    }, $terms);
}

/**
 * Liefert haeufig verwendete WordPress-Schlagwoerter.
 *
 * @return string[]
 */
function beitragseinreichung_get_frequent_tags($limit = 50)
{
    return array_map(static function (array $term) {
        return $term['name'];
    }, beitragseinreichung_get_frequent_tags_with_counts($limit));
}

/**
 * Liefert die editierbare Standardisierungsliste fuer die KI.
 *
 * @return string[]
 */
function beitragseinreichung_get_tag_standard_terms()
{
    $saved_terms = get_option('beitragseinreichung_tag_standard_terms', []);
    if (!empty($saved_terms) && is_array($saved_terms)) {
        return beitragseinreichung_parse_tags($saved_terms);
    }

    return [];
}

/**
 * Liefert das Tag-Maximum fuer KI-Vorschlaege.
 */
function beitragseinreichung_get_ai_tag_limit()
{
    $limit = (int) get_option('beitragseinreichung_ki_tags_max', 5);

    return max(1, min(12, $limit));
}
