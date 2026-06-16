<?php

defined('ABSPATH') || exit;

/**
 * Entfernt Emojis aus einem Text.
 */
function beitrag_remove_emojis($string)
{
    return preg_replace('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}]+/u', '', $string);
}

/**
 * Bereinigt einen Beitragstitel von KI-Markdown und begrenzt seine Laenge.
 */
function beitrag_bereinige_titel_text($titel, $fallback = '', $max_length = 90)
{
    $titel = beitrag_entferne_titel_formatierung($titel);
    $fallback = beitrag_entferne_titel_formatierung($fallback);

    if ($titel === '') {
        $titel = $fallback;
    }

    $titel_length = function_exists('mb_strlen') ? mb_strlen($titel) : strlen($titel);

    if ($titel_length > $max_length * 1.5 && $fallback !== '') {
        $titel = $fallback;
        $titel_length = function_exists('mb_strlen') ? mb_strlen($titel) : strlen($titel);
    }

    if ($titel_length > $max_length) {
        $titel = rtrim(wp_html_excerpt($titel, $max_length, ''), " \t\n\r\0\x0B.,;:-") . '...';
    }

    return $titel;
}

/**
 * Entfernt Markdown und HTML aus einem Titel.
 */
function beitrag_entferne_titel_formatierung($titel)
{
    $titel = wp_strip_all_tags((string) $titel);
    $titel = html_entity_decode($titel, ENT_QUOTES, get_bloginfo('charset'));
    $titel = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $titel);
    $titel = preg_replace('/[*_`~#>]+/', '', $titel);
    $titel = preg_replace('/\s+/', ' ', $titel);

    return trim($titel);
}

/**
 * Wandelt einfache Markdown-Inline-Formatierung in HTML um.
 */
function beitrag_formatiere_inline_markdown($text)
{
    // Fett: **text**
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);

    // Kursiv: *text*
    $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)\*(?!\*)/s', '<em>$1</em>', $text);

    // Links: [Text](URL)
    $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
        $label = esc_html($matches[1]);
        $url = esc_url($matches[2]);

        if ($url === '') {
            return $label;
        }

        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
    }, $text);

    return $text;
}

/**
 * Wandelt Markdown-aehnliche Textabschnitte in Gutenberg-Bloecke um.
 */
function beitrag_wandle_zu_gutenberg_blocks($text)
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    $lines = explode("\n", $text);

    $current_lines = [];
    $blocks = [];

    $flush_current_lines = function () use (&$current_lines, &$blocks) {
        if (empty($current_lines)) {
            return;
        }

        $blocks[] = beitrag_render_block_from_lines($current_lines);
        $current_lines = [];
    };

    foreach ($lines as $line) {
        $trimmed_line = trim($line);

        if ($trimmed_line === '') {
            $flush_current_lines();
            continue;
        }

        if (preg_match('/^#{1,6}\s+.+/', $trimmed_line)) {
            $flush_current_lines();
            $blocks[] = beitrag_render_block_from_lines([$trimmed_line]);
            continue;
        }

        $is_list_line = beitrag_ist_markdown_listenzeile($trimmed_line);
        $current_is_list = !empty($current_lines) && beitrag_ist_markdown_listenzeile($current_lines[0]);

        if (!empty($current_lines) && $is_list_line !== $current_is_list) {
            $flush_current_lines();
        }

        $current_lines[] = $trimmed_line;
    }

    $flush_current_lines();

    return implode("\n\n", $blocks);
}

/**
 * Prueft, ob eine Zeile wie ein Markdown-Listenpunkt beginnt.
 */
function beitrag_ist_markdown_listenzeile($line)
{
    return (bool) preg_match('/^\s*(?:[-*•]|–|—)\s+.+/u', (string) $line);
}

/**
 * Entfernt den Markdown-Listenmarker einer Zeile.
 */
function beitrag_entferne_markdown_listenmarker($line)
{
    return preg_replace('/^\s*(?:[-*•]|–|—)\s+/u', '', (string) $line);
}

/**
 * Rendert Zeilen als Gutenberg-Block.
 */
function beitrag_render_block_from_lines($lines)
{
    $text = implode("\n", $lines);
    $text = trim($text);

    // Ueberschrift?
    if (preg_match('/^(#{1,6})\s+(.+)/', $text, $matches)) {
        $level = strlen($matches[1]);
        $content = trim($matches[2]);
        return '<!-- wp:heading {"level":' . $level . '} -->' . "\n" . '<h' . $level . '>' . wp_kses_post(beitrag_formatiere_inline_markdown($content)) . '</h' . $level . '>' . "\n" . '<!-- /wp:heading -->';
    }

    // Liste?
    if (beitrag_ist_markdown_listenzeile($lines[0])) {
        $items = '';
        foreach ($lines as $line) {
            $line = beitrag_entferne_markdown_listenmarker($line);
            $items .= '<li>' . wp_kses_post(beitrag_formatiere_inline_markdown($line)) . '</li>';
        }
        return '<!-- wp:list --><ul>' . $items . '</ul><!-- /wp:list -->';
    }

    // Standard: Absatz
    return '<!-- wp:paragraph -->' . "\n" . '<p>' . wp_kses_post(beitrag_formatiere_inline_markdown($text)) . '</p>' . "\n" . '<!-- /wp:paragraph -->';
}
