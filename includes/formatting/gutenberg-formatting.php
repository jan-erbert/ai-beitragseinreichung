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
    // Inline-Code: `text`
    $text = preg_replace_callback('/`([^`]+)`/', function ($matches) {
        return '<code>' . esc_html($matches[1]) . '</code>';
    }, $text);

    // Fett: **text** und __text__
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.*?)__/s', '<strong>$1</strong>', $text);

    // Kursiv: *text* und _text_
    $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)\*(?!\*)/s', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!\w)_(?!_)(.+?)(?<!_)_(?!\w)/s', '<em>$1</em>', $text);

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

        if (beitrag_ist_markdown_ueberschrift($trimmed_line) || beitrag_ist_markdown_trenner($trimmed_line)) {
            $flush_current_lines();
            $blocks[] = beitrag_render_block_from_lines([$trimmed_line]);
            continue;
        }

        $line_type = beitrag_ermittle_markdown_block_typ($trimmed_line);
        $current_type = !empty($current_lines) ? beitrag_ermittle_markdown_block_typ($current_lines[0]) : '';

        if (!empty($current_lines) && $line_type !== $current_type) {
            $flush_current_lines();
        }

        $current_lines[] = $trimmed_line;
    }

    $flush_current_lines();

    return implode("\n\n", $blocks);
}

/**
 * Ermittelt den einfachen Markdown-Blocktyp einer Zeile.
 */
function beitrag_ermittle_markdown_block_typ($line)
{
    if (beitrag_ist_markdown_tabelle_zeile($line)) {
        return 'table';
    }

    if (beitrag_ist_markdown_nummerierte_listenzeile($line)) {
        return 'ordered-list';
    }

    if (beitrag_ist_markdown_listenzeile($line)) {
        return 'list';
    }

    if (beitrag_ist_markdown_zitatzeile($line)) {
        return 'quote';
    }

    return 'paragraph';
}

/**
 * Prueft, ob eine Zeile wie eine Markdown-Ueberschrift beginnt.
 */
function beitrag_ist_markdown_ueberschrift($line)
{
    return (bool) preg_match('/^#{1,6}\s+.+/', (string) $line);
}

/**
 * Prueft, ob eine Zeile wie ein Markdown-Listenpunkt beginnt.
 */
function beitrag_ist_markdown_listenzeile($line)
{
    return (bool) preg_match('/^\s*(?:[-*•]|–|—)\s+.+/u', (string) $line);
}

/**
 * Prueft, ob eine Zeile wie ein nummerierter Markdown-Listenpunkt beginnt.
 */
function beitrag_ist_markdown_nummerierte_listenzeile($line)
{
    return (bool) preg_match('/^\s*\d+[.)]\s+.+/', (string) $line);
}

/**
 * Entfernt den Markdown-Listenmarker einer Zeile.
 */
function beitrag_entferne_markdown_listenmarker($line)
{
    return preg_replace('/^\s*(?:[-*•]|–|—)\s+/u', '', (string) $line);
}

/**
 * Entfernt den Marker einer nummerierten Markdown-Liste.
 */
function beitrag_entferne_markdown_nummerierten_listenmarker($line)
{
    return preg_replace('/^\s*\d+[.)]\s+/', '', (string) $line);
}

/**
 * Prueft, ob eine Zeile wie ein Markdown-Zitat beginnt.
 */
function beitrag_ist_markdown_zitatzeile($line)
{
    return (bool) preg_match('/^\s*>\s*.+/', (string) $line);
}

/**
 * Entfernt den Markdown-Zitatmarker.
 */
function beitrag_entferne_markdown_zitatmarker($line)
{
    return preg_replace('/^\s*>\s?/', '', (string) $line);
}

/**
 * Prueft, ob eine Zeile ein Markdown-Trenner ist.
 */
function beitrag_ist_markdown_trenner($line)
{
    return (bool) preg_match('/^\s*(?:-{3,}|\*{3,}|_{3,})\s*$/', (string) $line);
}

/**
 * Prueft, ob eine Zeile wie eine Markdown-Tabellenzeile aussieht.
 */
function beitrag_ist_markdown_tabelle_zeile($line)
{
    $line = trim((string) $line);

    return strpos($line, '|') !== false && preg_match('/^\|?.+\|.+\|?$/', $line);
}

/**
 * Prueft, ob Zeilen zusammen eine valide einfache Markdown-Tabelle bilden.
 */
function beitrag_ist_markdown_tabelle($lines)
{
    if (count($lines) < 3 || !beitrag_ist_markdown_tabelle_trennzeile($lines[1])) {
        return false;
    }

    $header_count = count(beitrag_zerlege_markdown_tabelle_zeile($lines[0]));
    if ($header_count < 2) {
        return false;
    }

    foreach ($lines as $line) {
        if (count(beitrag_zerlege_markdown_tabelle_zeile($line)) !== $header_count) {
            return false;
        }
    }

    return true;
}

/**
 * Prueft, ob eine Markdown-Tabellenzeile die Header-Trennung beschreibt.
 */
function beitrag_ist_markdown_tabelle_trennzeile($line)
{
    $cells = beitrag_zerlege_markdown_tabelle_zeile($line);

    if (count($cells) < 2) {
        return false;
    }

    foreach ($cells as $cell) {
        if (!preg_match('/^:?-{3,}:?$/', trim($cell))) {
            return false;
        }
    }

    return true;
}

/**
 * Zerlegt eine einfache Markdown-Tabellenzeile in Zellen.
 */
function beitrag_zerlege_markdown_tabelle_zeile($line)
{
    $line = trim((string) $line);
    $line = trim($line, '|');

    return array_map('trim', explode('|', $line));
}

/**
 * Rendert eine einfache Markdown-Tabelle als Gutenberg-Table-Block.
 */
function beitrag_render_markdown_tabelle($lines)
{
    $headers = beitrag_zerlege_markdown_tabelle_zeile($lines[0]);
    $rows = array_slice($lines, 2);
    $thead = '';
    $tbody = '';

    foreach ($headers as $header) {
        $thead .= '<th>' . wp_kses_post(beitrag_formatiere_inline_markdown($header)) . '</th>';
    }

    foreach ($rows as $row) {
        $cells = beitrag_zerlege_markdown_tabelle_zeile($row);
        $tbody .= '<tr>';
        foreach ($cells as $cell) {
            $tbody .= '<td>' . wp_kses_post(beitrag_formatiere_inline_markdown($cell)) . '</td>';
        }
        $tbody .= '</tr>';
    }

    return '<!-- wp:table -->' . "\n"
        . '<figure class="wp-block-table"><table><thead><tr>' . $thead . '</tr></thead><tbody>' . $tbody . '</tbody></table></figure>' . "\n"
        . '<!-- /wp:table -->';
}

/**
 * Rendert Zeilen als Gutenberg-Block.
 */
function beitrag_render_block_from_lines($lines)
{
    $text = implode("\n", $lines);
    $text = trim($text);

    // Ueberschrift?
    if (beitrag_ist_markdown_tabelle($lines)) {
        return beitrag_render_markdown_tabelle($lines);
    }

    if (beitrag_ist_markdown_trenner($text)) {
        return '<!-- wp:separator -->' . "\n" . '<hr class="wp-block-separator has-alpha-channel-opacity"/>' . "\n" . '<!-- /wp:separator -->';
    }

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

    if (beitrag_ist_markdown_nummerierte_listenzeile($lines[0])) {
        $items = '';
        foreach ($lines as $line) {
            $line = beitrag_entferne_markdown_nummerierten_listenmarker($line);
            $items .= '<li>' . wp_kses_post(beitrag_formatiere_inline_markdown($line)) . '</li>';
        }
        return '<!-- wp:list {"ordered":true} --><ol>' . $items . '</ol><!-- /wp:list -->';
    }

    if (beitrag_ist_markdown_zitatzeile($lines[0])) {
        $quote_lines = array_map('beitrag_entferne_markdown_zitatmarker', $lines);
        $quote = implode('<br />', array_map('beitrag_formatiere_inline_markdown', $quote_lines));

        return '<!-- wp:quote -->' . "\n" . '<blockquote class="wp-block-quote"><p>' . wp_kses_post($quote) . '</p></blockquote>' . "\n" . '<!-- /wp:quote -->';
    }

    // Standard: Absatz
    return '<!-- wp:paragraph -->' . "\n" . '<p>' . wp_kses_post(beitrag_formatiere_inline_markdown($text)) . '</p>' . "\n" . '<!-- /wp:paragraph -->';
}
