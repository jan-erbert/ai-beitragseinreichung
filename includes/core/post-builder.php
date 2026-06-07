<?php

defined('ABSPATH') || exit;

/**
 * Bereitet uebergebene Galerie-IDs fuer die Ausgabe vor.
 *
 * @param mixed $gallery_ids Kommagetrennte Attachment-IDs.
 * @return int[]
 */
function beitragseinreichung_parse_gallery_ids($gallery_ids)
{
    $ids = array_filter(array_map('intval', explode(',', (string) $gallery_ids)));

    return array_values(array_unique($ids));
}

/**
 * Rendert ein einzelnes Zusatzbild fuer den Beitrag.
 */
function beitragseinreichung_render_single_gallery_image($attachment_id)
{
    $image = wp_get_attachment_image(
        $attachment_id,
        'large',
        false,
        [
            'class' => 'beitrag-gallery-single__image',
            'loading' => 'lazy',
        ]
    );

    if (!$image) {
        return '';
    }

    return '<!-- wp:html -->' . "\n"
        . '<figure class="beitrag-gallery-single">'
        . $image
        . '</figure>' . "\n"
        . '<!-- /wp:html -->';
}

/**
 * Rendert mehrere Zusatzbilder als Slider.
 */
function beitragseinreichung_render_gallery_slider(array $ids)
{
    $slides = [];
    $total = count($ids);

    foreach ($ids as $index => $id) {
        $image = wp_get_attachment_image(
            $id,
            'large',
            false,
            [
                'class' => 'beitrag-gallery-slider__image',
                'loading' => 'lazy',
            ]
        );

        if (!$image) {
            continue;
        }

        $slides[] = '<div class="beitrag-gallery-slider__slide" data-slide-index="' . esc_attr((string) $index) . '"' . ($index > 0 ? ' hidden' : '') . '>'
            . $image
            . '<span class="beitrag-gallery-slider__counter">' . esc_html(($index + 1) . ' / ' . $total) . '</span>'
            . '</div>';
    }

    if (empty($slides)) {
        return '';
    }

    return '<!-- wp:html -->' . "\n"
        . '<section class="beitrag-gallery-slider" data-current-slide="0" aria-label="' . esc_attr__('Zusätzliche Bilder', 'ai-beitragseinreichung') . '">'
        . '<div class="beitrag-gallery-slider__viewport">'
        . implode('', $slides)
        . '</div>'
        . '<div class="beitrag-gallery-slider__controls">'
        . '<button type="button" class="beitrag-gallery-slider__button" data-beitrag-slider-action="prev" aria-label="' . esc_attr__('Vorheriges Bild', 'ai-beitragseinreichung') . '">‹</button>'
        . '<button type="button" class="beitrag-gallery-slider__button" data-beitrag-slider-action="next" aria-label="' . esc_attr__('Nächstes Bild', 'ai-beitragseinreichung') . '">›</button>'
        . '</div>'
        . '</section>' . "\n"
        . '<!-- /wp:html -->';
}

/**
 * Fuegt ausgewaehlte Galeriebilder sauber am Ende eines Beitrags an.
 */
function beitragseinreichung_haenge_galeriebilder_an($beitrag_id, $gallery_ids)
{
    $ids = beitragseinreichung_parse_gallery_ids($gallery_ids);

    if (empty($ids)) {
        return;
    }

    $gallery_block = count($ids) > 1
        ? beitragseinreichung_render_gallery_slider($ids)
        : beitragseinreichung_render_single_gallery_image($ids[0]);

    if ($gallery_block === '') {
        return;
    }

    $aktueller_content = get_post_field('post_content', $beitrag_id);
    wp_update_post([
        'ID' => $beitrag_id,
        'post_content' => $aktueller_content . "\n\n" . $gallery_block,
    ]);
}

/**
 * Ermittelt die Empfaenger fuer neue Beitragseinreichungen.
 */
function beitragseinreichung_get_benachrichtigung_emails()
{
    $user_ids = array_map('intval', (array) get_option('beitragseinreichung_benachrichtigungs_user_ids', []));
    $emails = [];

    foreach ($user_ids as $uid) {
        $user = get_user_by('ID', $uid);
        if ($user && is_email($user->user_email)) {
            $emails[] = $user->user_email;
        }
    }

    if (get_option('beitragseinreichung_autor_notify')) {
        $autor = wp_get_current_user();
        if ($autor && is_email($autor->user_email) && !in_array($autor->user_email, $emails, true)) {
            $emails[] = $autor->user_email;
        }
    }

    return $emails;
}

/**
 * Sendet die Benachrichtigung zu einer neuen Beitragseinreichung.
 */
function beitragseinreichung_sende_beitrag_benachrichtigung($beitrag_id, $titel, $inhalt, $ki_aktiv, $modell, $zusatz, $excerpt = '', $gallery_ids = '')
{
    $emails = beitragseinreichung_get_benachrichtigung_emails();

    if (empty($emails)) {
        return;
    }

    $ki_info = $ki_aktiv ? 'Ja' : 'Nein';
    $modell_info = $ki_aktiv ? beitrag_get_ai_model_display_name($modell) : '–';
    $autor = get_user_by('ID', (int) get_post_field('post_author', $beitrag_id));
    $autor_name = $autor ? $autor->display_name : '';
    $kategorien = wp_get_post_categories($beitrag_id, ['fields' => 'names']);
    $tags = wp_get_post_tags($beitrag_id, ['fields' => 'names']);
    $edit_link = get_edit_post_link($beitrag_id);
    $featured_image = get_the_post_thumbnail(
        $beitrag_id,
        'medium',
        [
            'style' => 'max-width: 100%; height: auto; border-radius: 8px;',
        ]
    );
    $gallery_html = beitragseinreichung_render_mail_gallery($gallery_ids);
    $content_html = function_exists('beitragseinreichung_render_submission_preview_content')
        ? beitragseinreichung_render_submission_preview_content($inhalt)
        : wpautop(wp_kses_post($inhalt));

    $mail_html = '<html><body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1d2327;">';
    $mail_html .= '<div style="max-width:720px;margin:0 auto;padding:24px;">';
    $mail_html .= '<div style="background:#ffffff;border:1px solid #dcdcde;border-radius:10px;overflow:hidden;">';
    $mail_html .= '<div style="background:#2271b1;color:#ffffff;padding:18px 22px;">';
    $mail_html .= '<p style="margin:0 0 6px;font-size:13px;">Neue Beitragseinreichung</p>';
    $mail_html .= '<h1 style="margin:0;font-size:26px;line-height:1.25;">' . esc_html($titel) . '</h1>';
    $mail_html .= '</div>';
    $mail_html .= '<div style="padding:22px;">';
    $mail_html .= '<p style="margin:0 0 16px;color:#50575e;">Der Beitrag wurde gespeichert und wartet auf Prüfung.</p>';

    if ($featured_image) {
        $mail_html .= '<div style="margin:0 0 18px;">' . $featured_image . '</div>';
    }

    $mail_html .= '<table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:14px;">';
    $mail_html .= beitragseinreichung_render_mail_meta_row('Status', 'In Verarbeitung');
    $mail_html .= beitragseinreichung_render_mail_meta_row('Autor', $autor_name);
    $mail_html .= beitragseinreichung_render_mail_meta_row('Kategorie', implode(', ', $kategorien));
    $mail_html .= beitragseinreichung_render_mail_meta_row('Schlagwörter', implode(', ', $tags));
    $mail_html .= beitragseinreichung_render_mail_meta_row('KI-Optimiert', $ki_info);
    if ($ki_aktiv) {
        $mail_html .= beitragseinreichung_render_mail_meta_row('Modell', $modell_info);
    }
    $mail_html .= '</table>';

    if (!empty($excerpt)) {
        $mail_html .= '<div style="border-left:4px solid #2271b1;background:#f6f7f7;padding:12px 14px;margin:0 0 20px;">';
        $mail_html .= '<strong>Textauszug</strong><br>' . esc_html($excerpt);
        $mail_html .= '</div>';
    }

    $mail_html .= '<h2 style="font-size:18px;margin:0 0 10px;">Vorschau</h2>';
    $mail_html .= '<div style="font-size:15px;line-height:1.6;margin:0 0 20px;">' . wp_kses_post($content_html) . '</div>';

    if ($gallery_html !== '') {
        $mail_html .= '<h2 style="font-size:18px;margin:0 0 10px;">Zusätzliche Bilder</h2>';
        $mail_html .= $gallery_html;
    }

    if (!empty($zusatz)) {
        $mail_html .= '<div style="background:#fff8e5;border:1px solid #f0c36d;border-radius:8px;padding:12px 14px;margin:20px 0 0;">';
        $mail_html .= '<strong>KI-Hinweise</strong><br>' . nl2br(esc_html($zusatz));
        $mail_html .= '</div>';
    }

    if ($edit_link) {
        $mail_html .= '<p style="margin:24px 0 0;"><a href="' . esc_url($edit_link) . '" style="background:#2271b1;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:6px;display:inline-block;">Beitrag jetzt prüfen</a></p>';
    }

    $mail_html .= '</div></div></div></body></html>';

    wp_mail(
        $emails,
        '📝 Neuer Beitrag: ' . $titel,
        $mail_html,
        ['Content-Type: text/html; charset=UTF-8']
    );
}

/**
 * Rendert eine Tabellenzeile fuer Mail-Metadaten.
 */
function beitragseinreichung_render_mail_meta_row($label, $value)
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    return '<tr>'
        . '<th style="text-align:left;padding:7px 10px 7px 0;border-bottom:1px solid #f0f0f1;color:#50575e;width:150px;">' . esc_html($label) . '</th>'
        . '<td style="padding:7px 0;border-bottom:1px solid #f0f0f1;">' . esc_html($value) . '</td>'
        . '</tr>';
}

/**
 * Rendert ausgewaehlte Zusatzbilder fuer die Benachrichtigungsmail.
 */
function beitragseinreichung_render_mail_gallery($gallery_ids)
{
    $ids = beitragseinreichung_parse_gallery_ids($gallery_ids);
    $images = [];

    foreach ($ids as $id) {
        $image = wp_get_attachment_image(
            $id,
            'thumbnail',
            false,
            [
                'style' => 'width:96px;height:96px;object-fit:cover;border-radius:6px;margin:0 8px 8px 0;',
            ]
        );

        if ($image) {
            $images[] = $image;
        }
    }

    if (empty($images)) {
        return '';
    }

    return '<div style="display:block;margin:0 0 20px;">' . implode('', $images) . '</div>';
}
