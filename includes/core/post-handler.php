<?php

defined('ABSPATH') || exit;

// 3. Formular verarbeiten
add_action('admin_init', function () {
    if (
        isset($_POST['beitrag_nonce']) &&
        wp_verify_nonce($_POST['beitrag_nonce'], 'beitrag_einreichen') &&
        current_user_can('edit_posts')
    ) {
        // Globale Fehler-Variable zur Erkennung von Problemen bei der KI-Optimierung
        global $beitrag_ki_fehler;
        $beitrag_ki_fehler = false;

        $titel = sanitize_text_field($_POST['beitrag_titel']);
        $inhalt = wp_kses_post($_POST['beitrag_inhalt']);
        $tags = sanitize_text_field($_POST['beitrag_tags']);

        // KI-Aktivierung prüfen
        $ki_global = get_option('beitragseinreichung_ki_aktiv');
        $ki_aktiv = ($ki_global && !empty($_POST['beitrag_ki_individuell'])) ? true : false;

        if ($ki_aktiv) {
            // Originale merken
            $original_titel = $titel;
            $original_inhalt = $inhalt;
            // Modell vorher festlegen
            $modell = beitrag_normalize_ai_model(get_option('beitragseinreichung_ki_modell', beitrag_get_default_ai_model()));
            // Überarbeiten mit dem gewählten KI-Modell
            $zusatz = sanitize_textarea_field($_POST['beitrag_ki_hinweis'] ?? '');
            // Immer initialisieren, damit sie später verfügbar ist
            $stilgruppe_label = '';

            if ($ki_aktiv) {
                // Originale merken
                $original_titel = $titel;
                $original_inhalt = $inhalt;
                $modell = beitrag_normalize_ai_model(get_option('beitragseinreichung_ki_modell', beitrag_get_default_ai_model()));
                $zusatz = sanitize_textarea_field($_POST['beitrag_ki_hinweis'] ?? '');
                $stilgruppe_label = !empty($_POST['beitrag_ki_stilgruppe']) ? sanitize_text_field($_POST['beitrag_ki_stilgruppe']) : '';

                $titel = beitrag_ki_verbessere_text($titel, 'Beitragstitel', $modell, $zusatz);
                $inhalt = beitrag_ki_verbessere_text($inhalt, 'Beitragstext', $modell, $zusatz);
            }
        }

        // Beitrag anlegen
        $bereinigter_slug = sanitize_title(remove_emojis($titel));

        $beitrag_id = wp_insert_post([
            'post_title'   => $titel,
            'post_content' => beitrag_wandle_zu_gutenberg_blocks($inhalt),
            'post_status'  => 'in_verarbeitung',
            'post_type'    => 'post',
            'post_author'  => get_current_user_id(),
            'post_name'    => $bereinigter_slug,
        ]);
        // Textauszug speichern
        $excerpt = '';
        if (!empty($_POST['beitrag_excerpt_auto']) && $ki_aktiv) {
            $excerpt = beitrag_ki_verbessere_text($inhalt, 'Textauszug (kurz)', $modell, $zusatz);
        } elseif (!empty($_POST['beitrag_excerpt'])) {
            $excerpt = sanitize_text_field($_POST['beitrag_excerpt']);
        }

        if (!empty($excerpt)) {
            wp_update_post([
                'ID' => $beitrag_id,
                'post_excerpt' => $excerpt,
            ]);
        }

        // Kategorien setzen
        $kategorie_id = !empty($_POST['beitrag_kategorie']) ? (int) $_POST['beitrag_kategorie'] : 0;
        if ($kategorie_id > 0) {
            wp_set_post_categories($beitrag_id, [$kategorie_id]);
        }

        // Beitragsbild setzen
        if (!empty($_POST['beitragsbild_id'])) {
            set_post_thumbnail($beitrag_id, (int) $_POST['beitragsbild_id']);
        }

        // Galeriebilder einfügen
        if (!empty($_POST['gallery_ids'])) {
            $ids = array_map('intval', explode(',', $_POST['gallery_ids']));
            $anhang_html = '';
            foreach ($ids as $id) {
                $img_url = wp_get_attachment_image($id, 'large');
                if ($img_url) {
                    $anhang_html .= $img_url . "<br>";
                }
            }

            // Anhang ans Ende des Inhalts anhängen
            $aktueller_content = get_post_field('post_content', $beitrag_id);
            wp_update_post([
                'ID' => $beitrag_id,
                'post_content' => $aktueller_content . "\n\n" . '<hr><h3>Zusätzliche Bilder:</h3>' . "\n" . $anhang_html,
            ]);
        }


        if (!is_wp_error($beitrag_id)) {
            // Tags hinzufügen
            wp_set_post_tags($beitrag_id, $tags);

            // E-Mail an Admin
            // Empfänger aus Einstellungen holen
            $user_ids = get_option('beitragseinreichung_benachrichtigungs_user_ids', []);
            $emails = [];

            foreach ($user_ids as $uid) {
                $user = get_user_by('ID', $uid);
                if ($user && is_email($user->user_email)) {
                    $emails[] = $user->user_email;
                }
            }

            // Autoren-Benachrichtigung ergänzen (optional)
            if (get_option('beitragseinreichung_autor_notify')) {
                $autor = wp_get_current_user();
                if ($autor && is_email($autor->user_email) && !in_array($autor->user_email, $emails)) {
                    $emails[] = $autor->user_email;
                }
            }

            if (!empty($emails)) {
                $ki_info = $ki_aktiv ? 'Ja' : 'Nein';
                $modell_info = $ki_aktiv ? $modell : '–';

                $mail_html = '<html><body>';
                $mail_html .= '<h2>Ein neuer Beitrag wurde eingereicht</h2>';
                $mail_html .= '<p><strong>Titel:</strong> ' . esc_html($titel) . '</p>';
                $mail_html .= '<p><strong>Inhalt:</strong><br>' . wpautop(wp_kses_post($inhalt)) . '</p>';
                $mail_html .= '<hr>';
                $mail_html .= '<p><strong>KI-Optimiert:</strong> ' . $ki_info . '<br>';
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

            if ($ki_aktiv && !is_wp_error($beitrag_id)) {
                beitrag_ki_log_speichern(
                    $beitrag_id,
                    get_current_user_id(),
                    $original_titel,
                    $titel,
                    $original_inhalt,
                    $inhalt,
                    $modell,
                    $zusatz,
                    $stilgruppe_label
                );
            }

            // Weiterleitung
            if ($beitrag_ki_fehler) {
                wp_redirect(admin_url('admin.php?page=beitragseinreichung&erfolg=0&fehler=1'));
            } else {
                wp_redirect(admin_url('admin.php?page=beitragseinreichung&erfolg=1&beitrag_id=' . $beitrag_id));
            }
            exit;
        }
    }
});
