<?php

defined('ABSPATH') || exit;

/**
 * Fuegt ausgewaehlte Galeriebilder am Ende eines Beitrags an.
 */
function beitragseinreichung_haenge_galeriebilder_an($beitrag_id, $gallery_ids)
{
    $ids = array_map('intval', explode(',', (string) $gallery_ids));
    $anhang_html = '';

    foreach ($ids as $id) {
        $img_url = wp_get_attachment_image($id, 'large');
        if ($img_url) {
            $anhang_html .= $img_url . '<br>';
        }
    }

    if ($anhang_html === '') {
        return;
    }

    $aktueller_content = get_post_field('post_content', $beitrag_id);
    wp_update_post([
        'ID' => $beitrag_id,
        'post_content' => $aktueller_content . "\n\n" . '<hr><h3>Zusätzliche Bilder:</h3>' . "\n" . $anhang_html,
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
    $modell_info = $ki_aktiv ? $modell : '–';

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
