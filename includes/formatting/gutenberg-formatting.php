<?php

defined('ABSPATH') || exit;

/**
 * Entfernt Emojis aus einem Text.
 */
function remove_emojis($string)
{
    return preg_replace('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}]+/u', '', $string);
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
    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $text);

    return $text;
}

/**
 * Wandelt Textabschnitte in Gutenberg-Absatzbloecke um.
 */
function beitrag_wandle_zu_gutenberg_blocks($text)
{
    // Normalisiere Zeilenumbrueche
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));

    // Trenne bei zwei oder mehr Zeilenumbruechen (echte Absaetze)
    $absaetze = preg_split("/\n{2,}/", $text);

    $blocks = [];

    foreach ($absaetze as $absatz) {
        $absatz = trim($absatz);
        if ($absatz === '') continue;

        // Belasse harte Umbrueche im Absatz (z. B. Ergebniszeilen mit \n)
        $html = nl2br(beitrag_formatiere_inline_markdown($absatz));

        $blocks[] = '<!-- wp:paragraph -->' . "\n" . '<p>' . $html . '</p>' . "\n" . '<!-- /wp:paragraph -->';
    }

    return implode("\n\n", $blocks);
}

/**
 * Rendert Zeilen als Gutenberg-Block.
 */
function render_block_from_lines($lines)
{
    $text = implode("\n", $lines);
    $text = trim($text);

    // Ueberschrift?
    if (preg_match('/^#{1,6} (.+)/', $text, $matches)) {
        $level = strlen(explode(' ', $text)[0]);
        $content = trim($matches[1]);
        return '<!-- wp:heading {"level":' . $level . '} -->' . "\n" . '<h' . $level . '>' . esc_html($content) . '</h' . $level . '>' . "\n" . '<!-- /wp:heading -->';
    }

    // Liste?
    if (preg_match('/^[-*] (.+)/', $lines[0])) {
        $items = '';
        foreach ($lines as $line) {
            $line = ltrim((string) $line, '-* ');
            $items .= '<li>' . wp_kses_post(beitrag_formatiere_inline_markdown($line)) . '</li>';
        }
        return '<!-- wp:list --><ul>' . $items . '</ul><!-- /wp:list -->';
    }

    // Standard: Absatz
    return '<!-- wp:paragraph -->' . "\n" . '<p>' . wp_kses_post(beitrag_formatiere_inline_markdown($text)) . '</p>' . "\n" . '<!-- /wp:paragraph -->';
}
