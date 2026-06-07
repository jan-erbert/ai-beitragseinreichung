<?php

defined('ABSPATH') || exit;

/**
 * Liest und bereinigt Formularwerte fuer Vorschau und finale Speicherung.
 *
 * @param array<string, mixed> $source Eingabedaten aus POST oder FormData.
 * @return array<string, mixed>
 */
function beitragseinreichung_get_submission_input(array $source)
{
    $ki_global = get_option('beitragseinreichung_ki_aktiv');
    $ki_aktiv = ($ki_global && !empty($source['beitrag_ki_individuell'])) ? true : false;

    return [
        'title' => sanitize_text_field(wp_unslash($source['beitrag_titel'] ?? '')),
        'content' => wp_kses_post(wp_unslash($source['beitrag_inhalt'] ?? '')),
        'excerpt' => sanitize_text_field(wp_unslash($source['beitrag_excerpt'] ?? '')),
        'tags' => sanitize_text_field(wp_unslash($source['beitrag_tags'] ?? '')),
        'category_id' => !empty($source['beitrag_kategorie']) ? (int) $source['beitrag_kategorie'] : 0,
        'featured_image_id' => !empty($source['beitragsbild_id']) ? (int) $source['beitragsbild_id'] : 0,
        'gallery_ids' => sanitize_text_field(wp_unslash($source['gallery_ids'] ?? '')),
        'ki_active' => $ki_aktiv,
        'excerpt_auto' => !empty($source['beitrag_excerpt_auto']),
        'style_group' => !empty($source['beitrag_ki_stilgruppe']) ? sanitize_text_field(wp_unslash($source['beitrag_ki_stilgruppe'])) : '',
        'ai_hint' => sanitize_textarea_field(wp_unslash($source['beitrag_ki_hinweis'] ?? '')),
        'change_request' => sanitize_textarea_field(wp_unslash($source['beitrag_preview_change_request'] ?? '')),
    ];
}

/**
 * Erzeugt die finalen Beitragsdaten fuer eine Vorschau.
 *
 * @param array<string, mixed> $input Bereinigte Formulardaten.
 * @return array<string, mixed>
 */
function beitragseinreichung_build_submission_preview(array $input)
{
    $original_title = (string) $input['title'];
    $original_content = (string) $input['content'];
    $title = $original_title;
    $content = $original_content;
    $excerpt = (string) $input['excerpt'];
    $model = '';
    $ai_hint = (string) $input['ai_hint'];
    $style_group = (string) $input['style_group'];

    if (!empty($input['ki_active'])) {
        $model = beitrag_normalize_ai_model(get_option('beitragseinreichung_ki_modell', beitrag_get_default_ai_model()));
        $change_request = trim((string) $input['change_request']);

        if ($change_request !== '') {
            $ai_hint = trim($ai_hint . "\n\nAenderungswunsch aus der Vorschau: " . $change_request);
        }

        $ki_result = beitrag_ki_optimiere_beitrag(
            $title,
            $content,
            $model,
            $ai_hint,
            $style_group,
            !empty($input['excerpt_auto'])
        );

        $title = beitrag_bereinige_titel_text($ki_result['title'], $original_title);
        $content = $ki_result['content'];
        $excerpt = $ki_result['excerpt'];
    }

    $title = beitrag_bereinige_titel_text($title, $original_title);

    return [
        'title' => $title,
        'content' => $content,
        'excerpt' => $excerpt,
        'original_title' => $original_title,
        'original_content' => $original_content,
        'tags' => (string) $input['tags'],
        'category_id' => (int) $input['category_id'],
        'featured_image_id' => (int) $input['featured_image_id'],
        'gallery_ids' => (string) $input['gallery_ids'],
        'ki_active' => !empty($input['ki_active']),
        'model' => $model,
        'ai_hint' => $ai_hint,
        'style_group' => $style_group,
    ];
}

/**
 * Rendert Bildinformationen fuer die Vorschau.
 *
 * @param array<string, mixed> $preview Vorschau-Daten.
 * @return array<string, string>
 */
function beitragseinreichung_render_submission_preview_media(array $preview)
{
    $featured_image = '';
    $gallery = '';

    if (!empty($preview['featured_image_id'])) {
        $featured_image = wp_get_attachment_image(
            (int) $preview['featured_image_id'],
            'medium',
            false,
            [
                'class' => 'beitrag-preview__image',
                'loading' => 'lazy',
            ]
        );
    }

    $gallery_ids = beitragseinreichung_parse_gallery_ids($preview['gallery_ids'] ?? '');
    if (!empty($gallery_ids)) {
        $gallery_items = [];
        foreach ($gallery_ids as $gallery_id) {
            $image = wp_get_attachment_image(
                $gallery_id,
                'thumbnail',
                false,
                [
                    'class' => 'beitrag-preview__gallery-image',
                    'loading' => 'lazy',
                ]
            );

            if ($image) {
                $gallery_items[] = $image;
            }
        }

        if (!empty($gallery_items)) {
            $gallery = '<div class="beitrag-preview__gallery">' . implode('', $gallery_items) . '</div>';
        }
    }

    return [
        'featured_image_html' => $featured_image,
        'gallery_html' => $gallery,
    ];
}

/**
 * Rendert den Beitragstext fuer die Vorschau mit einfacher Markdown-Formatierung.
 */
function beitragseinreichung_render_submission_preview_content($content)
{
    $blocks = beitrag_wandle_zu_gutenberg_blocks((string) $content);

    return wp_kses_post(do_blocks($blocks));
}

/**
 * Erstellt eine serverseitige Vorschau per AJAX.
 */
add_action('wp_ajax_beitragseinreichung_preview_beitrag', function () {
    global $beitrag_ki_fehler, $beitrag_ki_fehler_meldung, $beitrag_ki_admin_benachrichtigung_unterdruecken;

    if (!current_user_can('beitragseinreichung_submit')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
    }

    check_ajax_referer('beitrag_einreichen', 'beitrag_nonce');

    $input = beitragseinreichung_get_submission_input($_POST);

    if ($input['title'] === '' || $input['content'] === '' || $input['tags'] === '') {
        wp_send_json_error(['message' => 'Bitte fuelle Titel, Inhalt und Schlagwoerter aus.']);
    }

    if (!empty($input['ki_active']) && $input['style_group'] === '') {
        wp_send_json_error(['message' => 'Bitte waehle einen Stil aus der Liste.']);
    }

    $beitrag_ki_fehler = false;
    $beitrag_ki_fehler_meldung = '';
    $beitrag_ki_admin_benachrichtigung_unterdruecken = true;
    $preview = beitragseinreichung_build_submission_preview($input);
    $beitrag_ki_admin_benachrichtigung_unterdruecken = false;

    if (!empty($input['ki_active']) && !empty($beitrag_ki_fehler)) {
        wp_send_json_error([
            'message' => $beitrag_ki_fehler_meldung ?: 'Die KI-Vorschau konnte nicht erstellt werden.',
        ]);
    }

    $media = beitragseinreichung_render_submission_preview_media($preview);
    $category = $preview['category_id'] > 0 ? get_category((int) $preview['category_id']) : null;

    wp_send_json_success([
        'preview' => [
            'title' => $preview['title'],
            'content' => $preview['content'],
            'content_html' => beitragseinreichung_render_submission_preview_content($preview['content']),
            'excerpt' => $preview['excerpt'],
            'tags' => $preview['tags'],
            'category_name' => $category && !is_wp_error($category) ? $category->name : '',
            'featured_image_html' => $media['featured_image_html'],
            'gallery_html' => $media['gallery_html'],
            'ki_active' => $preview['ki_active'],
            'model' => $preview['model'],
            'ai_hint' => $preview['ai_hint'],
            'style_group' => $preview['style_group'],
            'original_title' => $preview['original_title'],
            'original_content' => $preview['original_content'],
        ],
    ]);
});
