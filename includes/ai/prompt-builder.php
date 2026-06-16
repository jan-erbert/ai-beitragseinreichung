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
function beitrag_ki_baue_beitrag_prompt($titel, $inhalt, $stil, $zusatz = '', $excerpt_auto = false, $manual_tags = '', $generate_tags = true)
{
    $excerpt_regel = $excerpt_auto
        ? '- excerpt: 1-2 kurze Sätze als reine Textvorschau ohne Markdown.'
        : '- excerpt: leerer String.';
    $tag_limit = beitragseinreichung_get_ai_tag_limit();
    $ki_tags_aktiv = $generate_tags && get_option('beitragseinreichung_ki_tags_aktiv') ? true : false;
    $tag_regel = $ki_tags_aktiv
        ? '- tags: maximal ' . $tag_limit . ' passende Schlagwörter. Keine Hashtags, keine Emojis, keine erfundenen Orte oder Fakten.'
        : '- tags: leeres Array.';
    $manual_tags_text = implode(', ', beitragseinreichung_parse_tags($manual_tags));
    $standard_terms = beitragseinreichung_get_tag_standard_terms();
    if (get_option('beitragseinreichung_tags_context_aktiv')) {
        $standard_terms = beitragseinreichung_merge_tags($standard_terms, beitragseinreichung_get_frequent_tags(50));
    }
    $standard_terms_text = implode(', ', array_slice($standard_terms, 0, 80));
    $tag_hinweise = trim((string) get_option('beitragseinreichung_tags_ki_hinweise', ''));

    $prompt = <<<EOT
    Optimiere den folgenden WordPress-Beitrag redaktionell.

    Nutze den Originaltitel als Orientierung. Der neue Titel soll nah an der Nutzereingabe bleiben, aber mit Kontext des fertigen Beitragstextes und der Stilvorgaben verbessert werden.

    Gib ausschließlich valides JSON in exakt dieser Struktur zurück:
    {
      "title": "...",
      "content": "...",
      "excerpt": "...",
      "tags": []
    }

    Regeln:
    - title: maximal 90 Zeichen, eine Zeile, kein Markdown, keine HTML-Tags, keine Sternchen.
    - title: Emojis sind erlaubt, wenn sie zur Stilgruppe passen, aber maximal 1-2.
    - title: keine Fakten erfinden und keine Details überbetonen.
    - content: optimierter Beitragstext, keine Gutenberg-Kommentare, keine erfundenen Inhalte.
    - content: einfache Markdown-Formatierung ist erlaubt, wenn sie sinnvoll ist.
    - Sprache: Verwende korrektes Deutsch mit Umlauten und ß. Schreibe z. B. „ä“, „ö“, „ü“ und „ß“ statt „ae“, „oe“, „ue“ oder „ss“, sofern die normale deutsche Schreibweise das vorsieht.
    $excerpt_regel
    $tag_regel
    - tags: manuelle Schlagwörter beibehalten, wenn sie passen.
    - tags: vorhandene Standard-Schlagwörter bevorzugen, wenn sie inhaltlich passen, aber nicht erzwingen.
    - tags: keine Jahreszahl vorschlagen, wenn sie bereits manuell vorhanden ist.
    - Keine Erklärungen außerhalb des JSON.

    Stilvorgaben:
    $stil

    Originaltitel:
    $titel

    Beitragstext:
    $inhalt
    EOT;

    if ($manual_tags_text !== '') {
        $prompt .= "\n\nBereits gesetzte Schlagwörter:\n" . $manual_tags_text;
    }

    if ($tag_hinweise !== '') {
        $prompt .= "\n\nPriorisierte Schlagwort-Regeln:\n";
        $prompt .= "Diese Hinweise haben Vorrang vor allgemeinen Schlagwort-Empfehlungen und vor bevorzugten vorhandenen Schlagwörtern, solange sie nicht zu erfundenen Inhalten führen.\n";
        $prompt .= $tag_hinweise;
    }

    if ($standard_terms_text !== '') {
        $prompt .= "\n\nBevorzugte vorhandene Schlagwörter bei passendem Inhalt:\n" . $standard_terms_text;
    }

    if (!empty($zusatz)) {
        $prompt .= "\n\nZusatzhinweise:\n" . $zusatz;
    }

    return $prompt;
}
