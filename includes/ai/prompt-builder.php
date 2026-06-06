<?php

defined('ABSPATH') || exit;

/**
 * Ermittelt den kombinierten Stilprompt aus Stilgruppe und Grundstil.
 */
function beitrag_ki_ermittle_stil_prompt($stilgruppe_label = '')
{
    $grundstil = get_option('beitragseinreichung_ki_stil', '');
    $stilgruppen = get_option('beitragseinreichung_ki_stilgruppen', []);
    $stilbeschreibung = '';

    foreach ($stilgruppen as $gruppe) {
        if (isset($gruppe['label']) && $gruppe['label'] === $stilgruppe_label) {
            $stilbeschreibung = trim($gruppe['stil'] ?? '');
            break;
        }
    }

    return trim($stilbeschreibung . ($grundstil ? "\n\n" . $grundstil : ''));
}

/**
 * Baut den Prompt fuer die strukturierte Beitragsoptimierung.
 */
function beitrag_ki_baue_beitrag_prompt($titel, $inhalt, $stil, $zusatz = '', $excerpt_auto = false)
{
    $excerpt_regel = $excerpt_auto
        ? '- excerpt: 1-2 kurze Saetze als reine Textvorschau ohne Markdown.'
        : '- excerpt: leerer String.';

    $prompt = <<<EOT
    Optimiere den folgenden WordPress-Beitrag redaktionell.

    Nutze den Originaltitel als Orientierung. Der neue Titel soll nah an der Nutzereingabe bleiben, aber mit Kontext des fertigen Beitragstextes und der Stilvorgaben verbessert werden.

    Gib ausschliesslich valides JSON in exakt dieser Struktur zurueck:
    {
      "title": "...",
      "content": "...",
      "excerpt": "..."
    }

    Regeln:
    - title: maximal 90 Zeichen, eine Zeile, kein Markdown, keine HTML-Tags, keine Sternchen.
    - title: Emojis sind erlaubt, wenn sie zur Stilgruppe passen, aber maximal 1-2.
    - title: keine Fakten erfinden und keine Details ueberbetonen.
    - content: optimierter Beitragstext, keine Gutenberg-Kommentare, keine erfundenen Inhalte.
    - content: einfache Markdown-Formatierung ist erlaubt, wenn sie sinnvoll ist.
    $excerpt_regel
    - Keine Erklaerungen ausserhalb des JSON.

    Stilvorgaben:
    $stil

    Originaltitel:
    $titel

    Beitragstext:
    $inhalt
    EOT;

    if (!empty($zusatz)) {
        $prompt .= "\n\nZusatzhinweise:\n" . $zusatz;
    }

    return $prompt;
}
