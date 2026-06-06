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
    $user_ids = get_option('beitragseinreichung_benachrichtigungs_user_ids', []);
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
function beitragseinreichung_sende_beitrag_benachrichtigung($beitrag_id, $titel, $inhalt, $ki_aktiv, $modell, $zusatz)
{
    $emails = beitragseinreichung_get_benachrichtigung_emails();

    if (empty($emails)) {
        return;
    }

    $ki_info = $ki_aktiv ? 'Ja' : 'Nein';
    $modell_info = $ki_aktiv ? beitrag_get_ai_model_display_name($modell) : '–';

    $mail_html = '<html><body>';
    $mail_html .= '<h2>Ein neuer Beitrag wurde eingereicht</h2>';
    $mail_html .= '<p><strong>Titel:</strong> ' . esc_html($titel) . '</p>';
    $mail_html .= '<p><strong>Inhalt:</strong><br>' . wpautop(wp_kses_post($inhalt)) . '</p>';
    $mail_html .= '<hr>';
    $mail_html .= '<p><strong>KI-Optimiert:</strong> ' . esc_html($ki_info) . '<br>';
    $mail_html .= '<strong>Verwendetes Modell:</strong> ' . esc_html($modell_info) . '</p>';

    if (!empty($zusatz)) {
        $mail_html .= '<p><strong>KI-Hinweise:</strong><br>' . nl2br(esc_html($zusatz)) . '</p>';
    }

    $mail_html .= '<p><a href="' . esc_url(get_edit_post_link($beitrag_id)) . '">Beitrag jetzt prüfen &rarr;</a></p>';
    $mail_html .= '</body></html>';

    wp_mail(
        $emails,
        '📝 Neuer Beitrag: ' . $titel,
        $mail_html,
        ['Content-Type: text/html; charset=UTF-8']
    );
}
