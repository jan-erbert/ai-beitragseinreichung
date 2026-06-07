<?php

defined('ABSPATH') || exit;

// 3. Formular verarbeiten
add_action('admin_init', function () {
    if (wp_doing_ajax()) {
        return;
    }

    if (
        isset($_POST['beitrag_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['beitrag_nonce'])), 'beitrag_einreichen') &&
        current_user_can('beitragseinreichung_submit')
    ) {
        // Globale Fehler-Variable zur Erkennung von Problemen bei der KI-Optimierung
        global $beitrag_ki_fehler;
        $beitrag_ki_fehler = false;

        $input = beitragseinreichung_get_submission_input($_POST);
        $has_preview = !empty($_POST['beitrag_preview_ready']);
        $titel = sanitize_text_field(wp_unslash($_POST['beitrag_titel'] ?? ''));
        $inhalt = wp_kses_post(wp_unslash($_POST['beitrag_inhalt'] ?? ''));
        $tags = sanitize_text_field(wp_unslash($_POST['beitrag_tags'] ?? ''));
        $original_titel = $titel;
        $original_inhalt = $inhalt;
        $modell = '';
        $zusatz = '';
        $stilgruppe_label = '';
        $excerpt = '';
        $gallery_ids = '';

        // KI-Aktivierung prüfen
        $ki_global = get_option('beitragseinreichung_ki_aktiv');
        $ki_aktiv = ($ki_global && !empty($_POST['beitrag_ki_individuell'])) ? true : false;

        if ($has_preview) {
            $titel = sanitize_text_field(wp_unslash($_POST['beitrag_preview_title'] ?? ''));
            $inhalt = wp_kses_post(wp_unslash($_POST['beitrag_preview_content'] ?? ''));
            $excerpt = sanitize_text_field(wp_unslash($_POST['beitrag_preview_excerpt'] ?? ''));
            $original_titel = sanitize_text_field(wp_unslash($_POST['beitrag_preview_original_title'] ?? $input['title']));
            $original_inhalt = wp_kses_post(wp_unslash($_POST['beitrag_preview_original_content'] ?? $input['content']));
            $ki_aktiv = !empty($_POST['beitrag_preview_ki_active']);
            $modell = beitrag_normalize_ai_model(sanitize_text_field(wp_unslash($_POST['beitrag_preview_model'] ?? '')));
            $zusatz = sanitize_textarea_field(wp_unslash($_POST['beitrag_preview_ai_hint'] ?? ''));
            $stilgruppe_label = sanitize_text_field(wp_unslash($_POST['beitrag_preview_style_group'] ?? ''));
        } elseif ($ki_aktiv) {
            $modell = beitrag_normalize_ai_model(get_option('beitragseinreichung_ki_modell', beitrag_get_default_ai_model()));
            $zusatz = sanitize_textarea_field(wp_unslash($_POST['beitrag_ki_hinweis'] ?? ''));
            $stilgruppe_label = !empty($_POST['beitrag_ki_stilgruppe']) ? sanitize_text_field(wp_unslash($_POST['beitrag_ki_stilgruppe'])) : '';
            $excerpt_auto = !empty($_POST['beitrag_excerpt_auto']);
            $ki_ergebnis = beitrag_ki_optimiere_beitrag($titel, $inhalt, $modell, $zusatz, $stilgruppe_label, $excerpt_auto);

            $titel = $ki_ergebnis['title'];
            $titel = beitrag_bereinige_titel_text($titel, $original_titel);
            $inhalt = $ki_ergebnis['content'];
            $excerpt = $ki_ergebnis['excerpt'];
        }

        $titel = beitrag_bereinige_titel_text($titel, $original_titel);

        // Beitrag anlegen
        $bereinigter_slug = sanitize_title(beitrag_remove_emojis($titel));

        $beitrag_id = wp_insert_post([
            'post_title'   => $titel,
            'post_content' => beitrag_wandle_zu_gutenberg_blocks($inhalt),
            'post_status'  => 'in_verarbeitung',
            'post_type'    => 'post',
            'post_author'  => get_current_user_id(),
            'post_name'    => $bereinigter_slug,
        ]);

        if (is_wp_error($beitrag_id)) {
            wp_die(esc_html($beitrag_id->get_error_message()));
        }

        // Textauszug speichern
        if (empty($excerpt) && !empty($_POST['beitrag_excerpt'])) {
            $excerpt = sanitize_text_field(wp_unslash($_POST['beitrag_excerpt']));
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
            $gallery_ids = sanitize_text_field(wp_unslash($_POST['gallery_ids']));
            beitragseinreichung_haenge_galeriebilder_an($beitrag_id, $gallery_ids);
        }


        if ($beitrag_id) {
            // Tags hinzufügen
            wp_set_post_tags($beitrag_id, $tags);

            beitragseinreichung_sende_beitrag_benachrichtigung($beitrag_id, $titel, $inhalt, $ki_aktiv, $modell, $zusatz, $excerpt, $gallery_ids);

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
                wp_safe_redirect(admin_url('admin.php?page=beitragseinreichung&erfolg=0&fehler=1'));
            } else {
                wp_safe_redirect(admin_url('admin.php?page=beitragseinreichung&erfolg=1&beitrag_id=' . $beitrag_id));
            }
            exit;
        }
    }
});
