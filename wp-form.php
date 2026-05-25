<?php

/**
 * Plugin Name: 🧠 AI Beitragseinreichung
 * Plugin URI: https://jan-erbert.de
 * Description: Ermöglicht es berechtigten Nutzern, im Backend Beiträge mit Bild und Schlagwörtern einzureichen. Beiträge werden mit Status "In Verarbeitung" gespeichert und können durch AI verbessert werden.
 * Version: 1.1.4
 * Author: Jan Erbert
 * License: GPL2+
 */

// 0. Custom Capabilities registrieren (bei Plugin-Activation)
register_activation_hook(__FILE__, function () {
    beitragseinreichung_register_role_capabilities();
    // Verbindungstest beim Aktivieren durchführen
    beitragseinreichung_test_openai_verbindung();
});


// 1. Menüpunkte je nach Capability anzeigen
add_action('admin_menu', function () {
    // Nur wenn ein Recht vorhanden ist
    if (current_user_can('beitragseinreichung_submit') || current_user_can('beitragseinreichung_settings')) {
        add_menu_page(
            'Beitrag einreichen',
            'Beitrag einreichen',
            'read', // Berechtigung später prüfen
            'beitragseinreichung',
            function () {
                if (!current_user_can('beitragseinreichung_submit')) {
                    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.'));
                }
                beitragseinreichung_formular_anzeige();
            },
            'dashicons-edit',
            20
        );
    }

    if (current_user_can('beitragseinreichung_settings')) {
        add_submenu_page(
            'beitragseinreichung',
            'Beitragseinreichung – Einstellungen',
            'Einstellungen',
            'beitragseinreichung_settings',
            'beitragseinreichung_einstellungen',
            'beitragseinreichung_einstellungen_anzeige'
        );
    }

    if (current_user_can('beitragseinreichung_submit')) {
        add_submenu_page(
            'beitragseinreichung',
            'KI-Protokoll',
            'KI-Protokoll',
            'beitragseinreichung_submit',
            'beitragseinreichung_ki_protokoll',
            'beitragseinreichung_ki_log_anzeige'
        );
    }
});

add_action('wp_ajax_beitragseinreichung_test_openai_jetzt', function () {
    check_ajax_referer('test_openai_ajax');

    $status = beitragseinreichung_test_openai_verbindung();

    if ($status['status'] === 'erfolgreich') {
        wp_send_json_success($status['info']);
    } else {
        wp_send_json_error($status['info']);
    }
});

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/bootstrap.php';

// 2. Formular anzeigen
function beitragseinreichung_formular_anzeige()
{
    $excerpt_aktiv = get_option('beitragseinreichung_excerpt_aktiv', 1);
    $ki_global_aktiv = get_option('beitragseinreichung_ki_aktiv');
?>
    <div class="wrap">

        <picture id="beitragseinreichung-logo">
            <source srcset="<?php echo plugin_dir_url(__FILE__) . 'img/banner-small.png'; ?>" media="(max-width: 768px)">
            <img src="<?php echo plugin_dir_url(__FILE__) . 'img/banner-big.png'; ?>" alt="AI Beitragseinreichung Logo" style="width: 100%; max-width: 800px; height: auto;">
        </picture>

        <?php if (isset($_GET['erfolg']) && isset($_GET['beitrag_id'])):
            $link = admin_url('post.php?post=' . (int) $_GET['beitrag_id'] . '&action=edit');
        ?>
            <div class="notice notice-success">
                <p>Beitrag erfolgreich eingereicht! <a href="<?php echo esc_url($link); ?>" target="_blank">Beitrag anzeigen &rarr;</a></p>
            </div>
        <?php endif; ?>

        <form id="beitragseinreichung-formular" method="post" enctype="multipart/form-data" action="">
            <?php wp_nonce_field('beitrag_einreichen', 'beitrag_nonce'); ?>
            <h1>Beitrag einreichen</h1>
            <table class="form-table">
                <tr>
                    <th><label for="beitrag_titel">Titel <span class="required">*</span></label></th>
                    <td><input type="text" name="beitrag_titel" id="beitrag_titel" style="width: 100%;" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="beitrag_inhalt">Inhalt <span class="required">*</span></label></th>
                    <td><textarea name="beitrag_inhalt" id="beitrag_inhalt" rows="16" class="large-text" required></textarea></td>
                </tr>
                <?php if ($excerpt_aktiv): ?>
                    <tr id="textauszug-zeile">
                        <th><label for="beitrag_excerpt">Textauszug</label></th>
                        <td>
                            <textarea name="beitrag_excerpt" id="beitrag_excerpt" rows="3" class="large-text" placeholder="Kurze Lesevorschau (optional)"></textarea>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if ($ki_global_aktiv): ?>
                    <tr>
                        <td colspan="2">
                            <div class="ki-bereich">
                                <label>
                                    <input type="checkbox" name="beitrag_ki_individuell" id="beitrag_ki_individuell" value="1">
                                    <strong>Texte automatisch verbessern</strong> (Empfohlen)
                                </label>
                                <p class="description">Wenn aktiviert, werden Titel und Inhalt dieses Beitrags stilistisch mit GPT-4 überarbeitet.</p>
                                <?php if ($excerpt_aktiv): ?>
                                    <div id="ki-excerpt-option" style="margin-top: 10px; display: none;">
                                        <label>
                                            <input type="checkbox" name="beitrag_excerpt_auto" id="beitrag_excerpt_auto" value="1" checked>
                                            <strong>Textauszug automatisch generieren</strong>
                                        </label>
                                        <p class="description">Ein kurzer Vorschautext wird automatisch aus dem Inhalt erstellt.</p>
                                    </div>
                                <?php endif; ?>
                                <div id="ki-optionen-container" style="display: none;">
                                    <p><label for="beitrag_ki_stilgruppe">Stil der Ausgabe <span class="required">*</span></label><br>
                                        <select name="beitrag_ki_stilgruppe" id="beitrag_ki_stilgruppe">
                                            <option value="">– Stil auswählen –</option>
                                            <?php
                                            $stilgruppen = get_option('beitragseinreichung_ki_stilgruppen', []);
                                            foreach ($stilgruppen as $gruppe) {
                                                $ziel = isset($gruppe['ziel']) ? $gruppe['ziel'] : '';
                                                echo '<option value="' . esc_attr($gruppe['label']) . '" title="' . esc_attr($ziel) . '">' . esc_html($gruppe['label']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    <p id="stilgruppe-zieltext" style="font-size:0.9em; color:#900000;"></p>
                                    </p>
                                    <p>
                                        <label for="beitrag_ki_hinweis">Zusätzliche Hinweise für die KI (optional)</label><br>
                                        <textarea name="beitrag_ki_hinweis" id="beitrag_ki_hinweis" rows="3" class="large-text" placeholder="Optional: Bei besonderen zusätzlichen Stilwünschen oder Hinweisen."></textarea>
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>

                    </tbody>
                <?php endif; ?>
                <tr>
                    <th><label for="beitrag_tags">Schlagwörter <span class="required">*</span></label></th>
                    <td>
                        <input type="text" name="beitrag_tags" id="beitrag_tags" class="regular-text" placeholder="z.B. Marathon, Bad Kreuznach, 2025">
                        <p id="tag-hinweis" style="display:none; color:#a00; font-size:0.9em; margin-top:6px;">
                            ⚠️ Bitte trenne mehrere Schlagwörter durch Kommata – z. B. <em>2025, Outdoor Sport, Frankfurt</em>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="beitrag_kategorie">Kategorie <span class="required">*</span></label></th>
                    <td>
                        <select name="beitrag_kategorie" id="beitrag_kategorie">
                            <?php
                            $kategorien = get_categories(['hide_empty' => false]);
                            $standard_ids = get_option('beitragseinreichung_standard_kategorien', []);
                            $standard_id = is_array($standard_ids) && count($standard_ids) > 0 ? $standard_ids[0] : null;

                            foreach ($kategorien as $kategorie) {
                                $selected = ($kategorie->term_id == $standard_id) ? 'selected' : '';
                                echo '<option value="' . esc_attr($kategorie->term_id) . '" ' . $selected . '>' . esc_html($kategorie->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Beitragsbild</th>
                    <td>
                        <button id="select_beitragsbild" class="button">Beitragsbild auswählen</button><br><br>
                        <div id="beitragsbild_preview"></div>
                        <input type="hidden" name="beitragsbild_id" id="beitragsbild_id" value="">
                    </td>
                </tr>
                <tr>
                    <th>Zusätzliche Bilder</th>
                    <td>
                        <button id="select_gallery" class="button">Zusätzliche Bilder auswählen</button><br><br>
                        <div id="gallery_preview"></div>
                        <input type="hidden" name="gallery_ids" id="gallery_ids" value="">
                    </td>
                </tr>

            </table>

            <?php submit_button('Beitrag einreichen'); ?>
            <div id="submit-loader" style="display:none;">
                <div class="submit-loader-inner">
                    <div class="submit-loader-bar"></div>
                    <p>Dein Beitrag wird verarbeitet …</p>
                </div>
            </div>
            <div id="lottie-loader" style="display: none;">
                <lottie-player
                    src="<?php echo plugin_dir_url(__FILE__) . 'assets/lottie/ki-animation.json'; ?>"
                    background="transparent"
                    speed="1"
                    style="max-width: 35vw; height: auto;"
                    loop
                    autoplay>
                </lottie-player>
                <p style="margin-top: 1em; font-size: 1.2em;">⏳ Dein Beitrag wird eingereicht...</p>
            </div>
        </form>
        <p style="margin-top: 40px; font-size: 0.95em;">
            ℹ️ <a href="https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Beitrag-einreichen" target="_blank" rel="noopener noreferrer">
                Anleitung zur Beitragseinreichung im Wiki anzeigen →
            </a>
        </p>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('beitrag_ki_stilgruppe');
            const zielAnzeigen = document.getElementById('stilgruppe-zieltext');

            const stilgruppen = <?php echo json_encode(get_option('beitragseinreichung_ki_stilgruppen', [])); ?>;

            function updateZieltext() {
                const selectedStil = select.value;
                const gruppe = stilgruppen.find(g => g.stil === selectedStil);
                if (gruppe && gruppe.ziel) {
                    zielAnzeigen.textContent = "Stilgruppen Ziel: " + gruppe.ziel;
                } else {
                    zielAnzeigen.textContent = '';
                }
            }

            select.addEventListener('change', updateZieltext);
            updateZieltext();
        });
        document.addEventListener('DOMContentLoaded', function() {
            const tagInput = document.getElementById('beitrag_tags');
            const hinweis = document.getElementById('tag-hinweis');

            function triggerCheck() {
                const val = tagInput.value.trim();
                const hasMultipleWords = val.split(' ').length >= 2;
                const hasComma = val.includes(',');

                if (hasMultipleWords && !hasComma) {
                    hinweis.style.display = 'block';
                } else {
                    hinweis.style.display = 'none';
                }
            }

            if (tagInput) {
                tagInput.addEventListener('input', triggerCheck);
                triggerCheck(); // Initial prüfen
            }
        });
    </script>
<?php
}

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
            $modell = get_option('beitragseinreichung_ki_modell', 'gpt-4-turbo');
            // Überarbeiten mit GPT-4
            $zusatz = sanitize_textarea_field($_POST['beitrag_ki_hinweis'] ?? '');
            // Immer initialisieren, damit sie später verfügbar ist
            $stilgruppe_label = '';

            if ($ki_aktiv) {
                // Originale merken
                $original_titel = $titel;
                $original_inhalt = $inhalt;
                $modell = get_option('beitragseinreichung_ki_modell', 'gpt-4-turbo');
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

// 4. Custom Post Status registrieren
add_action('init', function () {
    register_post_status('in_verarbeitung', [
        'label' => 'In Verarbeitung',
        'public' => false,
        'internal' => true,
        'label_count' => _n_noop('In Verarbeitung <span class="count">(%s)</span>', 'In Verarbeitung <span class="count">(%s)</span>'),
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'post_type' => ['post'],
    ]);
});

// 5. JavaScript für Media Picker und Validierung
add_action('admin_footer', function () {
    echo '<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>';
    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_beitragseinreichung') return;
?>
    <script>
        jQuery(document).ready(function($) {
            let frame_featured, frame_gallery;

            // Stilgruppe + KI-Hinweise nur anzeigen, wenn Checkbox aktiv
            $('#beitrag_ki_individuell').on('change', function() {
                // Textauszug automatisch generieren ein-/ausblenden
                if ($(this).is(':checked')) {
                    $('#ki-excerpt-option').show();
                    $('#beitrag_excerpt_auto').prop('checked', true);
                } else {
                    $('#ki-excerpt-option').hide();
                    $('#beitrag_excerpt_auto').prop('checked', false);
                }
                if ($(this).is(':checked')) {
                    $('#ki-optionen-container').show();
                } else {
                    $('#ki-optionen-container').hide();
                }
            });

            function updateExcerptVisibility() {
                if ($('#beitrag_ki_individuell').is(':checked') && $('#beitrag_excerpt_auto').is(':checked')) {
                    $('#textauszug-zeile').hide();
                } else {
                    $('#textauszug-zeile').show();
                }
            }
            $('#beitrag_ki_individuell, #beitrag_excerpt_auto').on('change', updateExcerptVisibility);
            updateExcerptVisibility();


            // Initial prüfen bei Seitenladezeit
            if ($('#beitrag_ki_individuell').is(':checked')) {
                if ($('#beitrag_ki_individuell').is(':checked')) {
                    $('#ki-optionen-container').show();
                    $('#ki-excerpt-option').show();
                    $('#beitrag_excerpt_auto').prop('checked', true);
                } else {
                    $('#ki-optionen-container').hide();
                    $('#ki-excerpt-option').hide();
                    $('#beitrag_excerpt_auto').prop('checked', false);
                }
                $('#ki-optionen-container').show();
            }
            // Media Picker für Beitragsbild
            $('#select_beitragsbild').on('click', function(e) {
                e.preventDefault();
                if (frame_featured) {
                    frame_featured.open();
                    return;
                }
                frame_featured = wp.media({
                    title: 'Beitragsbild auswählen',
                    button: {
                        text: 'Bild verwenden'
                    },
                    multiple: false
                });

                frame_featured.on('select', function() {
                    const attachment = frame_featured.state().get('selection').first().toJSON();
                    $('#beitragsbild_id').val(attachment.id);
                    $('#beitragsbild_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:150px;">');
                });

                frame_featured.open();
            });

            // Media Picker für Galerie
            $('#select_gallery').on('click', function(e) {
                e.preventDefault();
                if (frame_gallery) {
                    frame_gallery.open();
                    return;
                }
                frame_gallery = wp.media({
                    title: 'Zusätzliche Bilder auswählen',
                    button: {
                        text: 'Bilder hinzufügen'
                    },
                    multiple: true
                });

                frame_gallery.on('select', function() {
                    const attachments = frame_gallery.state().get('selection').toJSON();
                    const ids = attachments.map(att => att.id).join(',');
                    $('#gallery_ids').val(ids);
                    let hinweis = '';
                    if (attachments.length > 0) {
                        hinweis = '<p style="margin-top:6px;color:#d63638;"><strong>Hinweis:</strong> Zusätzliche Bilder werden zunächst am Ende des Beitrags eingefügt und müssen ggf. im Editor nachträglich korrekt eingerückt oder positioniert werden.</p>';
                    }
                    $('#gallery_preview').html('Anzahl ausgewählter Bilder: ' + attachments.length + hinweis);
                });

                frame_gallery.open();
            });

            // Bestätigung vor Absenden – mit Warnung bei fehlendem Bild
            $(document).on('submit', '#beitragseinreichung-formular', function(e) {
                if ($('#beitrag_ki_individuell').is(':checked') && !$('#beitrag_ki_stilgruppe').val()) {
                    alert('Bitte wähle einen Stil aus der Liste.');
                    e.preventDefault();
                    return;
                }
                const title = $('#beitrag_titel').val().trim();
                const content = $('#beitrag_inhalt').val().trim();
                const tags = $('#beitrag_tags').val().trim();
                const hasImage = $('#beitragsbild_id').val();

                if (!title || !content || !tags) {
                    alert('Bitte fülle alle Pflichtfelder aus: Titel, Inhalt und Schlagwörter.');
                    e.preventDefault();
                    return;
                }

                let message = "Möchtest du den Beitrag wirklich einreichen?";
                if (!hasImage) {
                    message += "\n\n⚠️ Hinweis: Du hast kein Beitragsbild ausgewählt. Das Beitragsbild ist wichtig für die Darstellung und wird empfohlen.";
                }
                if (!$('#beitrag_ki_individuell').is(':checked')) {
                    message += "\n\n⚠️ Hinweis: Du hast die automatische Textverbesserung nicht aktiviert.\nDer Beitrag könnte dadurch an Qualität und Stil verlieren.\nDie KI passt Texte automatisch an den Stil der Webseite an.";
                }
                if (!confirm(message)) {
                    e.preventDefault();
                    return;
                }
                // Nur passenden Loader anzeigen
                if ($('#beitrag_ki_individuell').is(':checked')) {
                    $('#lottie-loader').fadeIn();
                    $('#submit-loader').hide();
                    $('html, body').animate({
                        scrollTop: $('#lottie-loader').offset().top - 40
                    }, 500);
                } else {
                    $('#submit-loader').fadeIn();
                    $('#lottie-loader').hide();
                    $('html, body').animate({
                        scrollTop: $('#submit-loader').offset().top - 40
                    }, 500);
                }

            });
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get('fehler') === '1') {
                $('#submit-loader, #lottie-loader').hide();
                $('body').append(`
                    <div id="error-overlay" style="position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(220, 53, 69, 0.85); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; text-align: center; display: none;">
                        <button id="close-error" style="position: absolute; top: 20px; right: 30px; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">&times;</button>
                        <h2 style="font-size: 2em; margin-top: 0;">❌ Fehler bei der KI-Optimierung</h2>
                        <div id="error-animation-container" style="margin: 20px 0;">
                            <lottie-player
                                src="<?php echo plugin_dir_url(__FILE__) . 'assets/lottie/error-animation.json'; ?>"
                                background="transparent"
                                speed="0.7"
                                style="width: 200px; height: 200px;"
                                autoplay
                                loop="false">
                            </lottie-player>
                        </div>
                        <p style="margin: 15px 0 20px;">Leider konnte dein Beitrag nicht automatisch verbessert werden.</p>
                        <div style="display: flex; gap: 20px; margin-top: 20px;">
                            <button id="retry-btn" class="button button-primary custom-hover" style="padding: 10px 20px; font-size: 16px; border-radius: 5px;">🔄 Erneut versuchen</button>
                            <button id="use-original-btn" class="button button-secondary custom-hover" style="padding: 10px 20px; font-size: 16px; border-radius: 5px;">➡️ Original verwenden</button>
                        </div>
                    </div>
                `);

                // Nach kurzem Timeout einblenden (wirkt angenehmer)
                setTimeout(function() {
                    $('#error-overlay').fadeIn(300);
                }, 100);

                $('#close-error').on('click', function() {
                    $('#error-overlay').fadeOut();
                });

                $('#retry-btn').on('click', function() {
                    window.location.reload();
                });

                $('#use-original-btn').on('click', function() {
                    window.location.href = '<?php echo admin_url('admin.php?page=beitragseinreichung'); ?>';
                });

                // Button Hover Effects
                $(document).on('mouseenter', '.custom-hover', function() {
                    $(this).css('filter', 'brightness(1.2)');
                }).on('mouseleave', '.custom-hover', function() {
                    $(this).css('filter', 'brightness(1)');
                });
            }

            if (urlParams.get('erfolg') === '1' && urlParams.get('beitrag_id')) {
                const beitragID = urlParams.get('beitrag_id');

                $('#submit-loader, #lottie-loader').hide();
                $('body').append(`
                    <div id="success-overlay" style="position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(40, 167, 69, 0.85); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; text-align: center;">
                        <button id="close-success" style="position: absolute; top: 20px; right: 30px; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">&times;</button>
                        <h2 style="font-size: 2em; margin-top: 0;">✅ Beitrag erfolgreich eingereicht!</h2>
                        <div id="success-animation-container" style="margin: 20px 0;">
                            <lottie-player
                                src="<?php echo plugin_dir_url(__FILE__) . 'assets/lottie/success-animation.json'; ?>"
                                background="transparent"
                                speed="0.7"
                                style="width: 200px; height: 200px;"
                                autoplay>
                            </lottie-player>
                        </div>
                        <p style="margin: 15px 0 20px;">Dein Beitrag wurde gespeichert und wartet auf Prüfung.</p>
                        <div style="display: flex; gap: 20px; margin-top: 20px;">
                            <button class="button button-primary custom-hover" onclick="window.location.href='<?php echo admin_url('admin.php?page=beitragseinreichung'); ?>'" style="padding: 10px 20px; font-size: 16px; border-radius: 5px; background: #2271b1; border-color: #2271b1; color: white;">Neuen Beitrag einreichen</button>
                            <button 
                            id="btn-pruefen" 
                            class="button button-primary custom-hover" 
                            style="padding: 10px 20px; font-size: 16px; border-radius: 5px; background: #2271b1; border-color: #2271b1; color: white;"
                            data-beitrag-id="${beitragID}">
                            📝 Beitrag jetzt prüfen
                            </button>
                        </div>
                    </div>
                `);
                $(document).on('click', '#btn-pruefen', function() {
                    const beitragID = $(this).data('beitrag-id');
                    if (beitragID) {
                        window.location.href = '<?php echo admin_url('post.php'); ?>?post=' + beitragID + '&action=edit';
                    }
                });

                $('#close-success').on('click', function() {
                    $('#success-overlay').fadeOut();
                });
            }

            // Zusätzlicher Hover-Effekt für schöne Buttons:
            $(document).on('mouseenter', '.custom-hover', function() {
                $(this).css('filter', 'brightness(1.2)');
            }).on('mouseleave', '.custom-hover', function() {
                $(this).css('filter', 'brightness(1)');
            });
        });
    </script>
<?php
});


// 7. Anzeige und Verarbeitung der Einstellungen

function beitragseinreichung_einstellungen_anzeige()
{
    $ist_admin = current_user_can('beitragseinreichung_admin');
    if (isset($_POST['beitrag_einstellungen_nonce']) && wp_verify_nonce($_POST['beitrag_einstellungen_nonce'], 'speichern_beitrag_einstellungen')) {
        $kategorie = isset($_POST['standard_kategorie']) ? [(int) $_POST['standard_kategorie']] : [];
        $empfaenger = isset($_POST['empfaenger_user_ids']) ? array_map('intval', $_POST['empfaenger_user_ids']) : [];
        $ki_aktiv = isset($_POST['beitragseinreichung_ki_aktiv']) ? (int) $_POST['beitragseinreichung_ki_aktiv'] : 0;
        update_option('beitragseinreichung_ki_aktiv', $ki_aktiv);
        $stilgruppen = [];
        if (!empty($_POST['stilgruppe_label']) && !empty($_POST['stilgruppe_stil'])) {
            foreach ($_POST['stilgruppe_label'] as $i => $label) {
                $label = sanitize_text_field($label);
                $stil = sanitize_text_field($_POST['stilgruppe_stil'][$i]);
                if (!empty($label) && !empty($stil)) {
                    $ziel = sanitize_text_field($_POST['stilgruppe_ziel'][$i] ?? '');
                    $stilgruppen[] = [
                        'label' => $label,
                        'stil' => $stil,
                        'ziel' => $ziel
                    ];
                }
            }
        }
        update_option('beitragseinreichung_ki_stilgruppen', $stilgruppen);
        $ki_stil = isset($_POST['beitragseinreichung_ki_stil']) ? sanitize_text_field($_POST['beitragseinreichung_ki_stil']) : '';
        $ki_modell = isset($_POST['beitragseinreichung_ki_modell']) ? sanitize_text_field($_POST['beitragseinreichung_ki_modell']) : 'gpt-4-turbo';
        $autor_notify = isset($_POST['beitragseinreichung_autor_notify']) ? 1 : 0;
        update_option('beitragseinreichung_autor_notify', $autor_notify);
        update_option('beitragseinreichung_ki_modell', $ki_modell);
        if (!defined('OPENAI_API_KEY') && isset($_POST['beitragseinreichung_api_key'])) {
            $key = trim(sanitize_text_field($_POST['beitragseinreichung_api_key']));
            update_option('beitragseinreichung_api_key', $key);
        }
        $excerpt_aktiv = isset($_POST['beitragseinreichung_excerpt_aktiv']) ? (int) $_POST['beitragseinreichung_excerpt_aktiv'] : 1;
        update_option('beitragseinreichung_excerpt_aktiv', $excerpt_aktiv);
        update_option('beitragseinreichung_ki_stil', $ki_stil);
        update_option('beitragseinreichung_standard_kategorien', $kategorie);
        update_option('beitragseinreichung_benachrichtigungs_user_ids', $empfaenger);

        echo '<div class="updated"><p>Einstellungen gespeichert.</p></div>';
        // Verbindung testen nach dem Speichern
        beitragseinreichung_test_openai_verbindung();
    }

    $standard_ids = get_option('beitragseinreichung_standard_kategorien', []);
    $user_ids = get_option('beitragseinreichung_benachrichtigungs_user_ids', []);

    $kategorien = get_categories(['hide_empty' => false]);
    $nutzer = get_users(['fields' => ['ID', 'display_name', 'user_email']]);
?>
    <div class="wrap">
        <h1>Beitragseinreichung – Einstellungen</h1>
        <form method="post">
            <?php wp_nonce_field('speichern_beitrag_einstellungen', 'beitrag_einstellungen_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="standard_kategorie">Standard-Kategorie</label></th>
                    <td>
                        <select name="standard_kategorie" id="standard_kategorie">
                            <option value="0">– Keine Auswahl –</option>
                            <?php
                            foreach ($kategorien as $kat) {
                                $selected = ($standard_ids && $kat->term_id == $standard_ids[0]) ? 'selected' : '';
                                echo '<option value="' . esc_attr($kat->term_id) . '" ' . $selected . '>' . esc_html($kat->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <?php if ($ist_admin): ?>
                    <tr>
                        <th scope="row"><label for="empfaenger_user_ids">Benachrichtigungs-Empfänger</label></th>
                        <td>
                            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                                <?php foreach ($nutzer as $nutzer_obj): ?>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="empfaenger_user_ids[]" value="<?php echo esc_attr($nutzer_obj->ID); ?>"
                                            <?php checked(in_array($nutzer_obj->ID, $user_ids)); ?>>
                                        <?php echo esc_html($nutzer_obj->display_name . ' (' . $nutzer_obj->user_email . ')'); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="description">Wähle einen oder mehrere Benutzer aus, die bei neuen Beiträgen benachrichtigt werden sollen.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row"><label for="beitragseinreichung_autor_notify">Autor Benachrichtigungsmail senden</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="beitragseinreichung_autor_notify" id="beitragseinreichung_autor_notify" value="1"
                                <?php checked(get_option('beitragseinreichung_autor_notify'), 1); ?>>
                            Ja, dem Beitragseinreicher eine E-Mail senden
                        </label>
                        <p class="description">Wenn aktiviert, erhält der Autor des Beitrags eine E-Mail, sofern er nicht ohnehin in der Empfängerliste ist.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="beitragseinreichung_excerpt_aktiv">Textvorschau (Textauszug)</label></th>
                    <td>
                        <select name="beitragseinreichung_excerpt_aktiv"
                            id="beitragseinreichung_excerpt_aktiv"
                            <?php echo $ist_admin ? '' : 'disabled'; ?>>
                            <option value="1" <?php selected(get_option('beitragseinreichung_excerpt_aktiv'), 1); ?>>Aktiviert</option>
                            <option value="0" <?php selected(get_option('beitragseinreichung_excerpt_aktiv'), 0); ?>>Deaktiviert</option>
                        </select>
                        <p class="description">Wenn deaktiviert, wird das Feld für den Textauszug im Formular nicht angezeigt.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="beitragseinreichung_ki_aktiv">KI aktivieren</label></th>
                    <td>
                        <?php
                        $status = get_option('beitragseinreichung_api_status');
                        $key_valid = $status && $status['status'] === 'erfolgreich';
                        ?>
                        <select name="beitragseinreichung_ki_aktiv"
                            id="beitragseinreichung_ki_aktiv"
                            <?php echo ($ist_admin && $key_valid) ? '' : 'disabled'; ?>
                            title="<?php echo esc_attr($key_valid ? 'Nur Admins dürfen diese Einstellung ändern.' : 'Ein gültiger API-Key ist erforderlich.'); ?>">
                            <option value="0" <?php selected(get_option('beitragseinreichung_ki_aktiv'), 0); ?>>Deaktiviert</option>
                            <option value="1" <?php selected(get_option('beitragseinreichung_ki_aktiv'), 1); ?>>Aktiviert</option>
                        </select>
                        <p class="description">Wenn aktiviert, kannst du im Formular festlegen, ob Titel und Inhalt per GPT-API verbessert werden.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Stilgruppen verwalten</th>
                    <td>
                        <select id="stilgruppe-auswahl" style="width: 300px;">
                            <option value="">-- Stilgruppe auswählen --</option>
                        </select>
                        <button type="button" class="button" id="neue-stilgruppe">+ Neue Stilgruppe</button>

                        <div id="stilgruppe-editor" style="margin-top: 20px; display: none;">
                            <label for="stilgruppe-label">Bezeichnung:</label><br>
                            <input type="text" id="stilgruppe-label" style="width: 100%;"><br><br>
                            <label for="stilgruppe-stil">Stilbeschreibung:</label><br>
                            <textarea id="stilgruppe-stil" rows="16" style="width: 100%;"></textarea><br><br>
                            <label for="stilgruppe-ziel">Ziel (optional):</label><br>
                            <input type="text" id="stilgruppe-ziel" style="width: 100%;"><br><br>
                            <button type="button" class="button button-primary" id="stilgruppe-speichern">Speichern</button>
                            <button type="button" class="button button-secondary" id="stilgruppe-loeschen">Löschen</button>
                        </div>

                        <input type="hidden" name="stilgruppe_label[]">
                        <input type="hidden" name="stilgruppe_stil[]">
                    </td>
                </tr>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const stilgruppen = <?php echo json_encode(get_option('beitragseinreichung_ki_stilgruppen', [])); ?>;

                        const auswahl = document.getElementById('stilgruppe-auswahl');
                        const editor = document.getElementById('stilgruppe-editor');
                        const inputLabel = document.getElementById('stilgruppe-label');
                        const inputStil = document.getElementById('stilgruppe-stil');
                        const speichernBtn = document.getElementById('stilgruppe-speichern');
                        const loeschenBtn = document.getElementById('stilgruppe-loeschen');
                        const neueBtn = document.getElementById('neue-stilgruppe');

                        function updateDropdown() {
                            auswahl.innerHTML = '<option value="">-- Stilgruppe auswählen --</option>';
                            stilgruppen.forEach((gruppe, index) => {
                                const option = document.createElement('option');
                                option.value = index;
                                option.textContent = gruppe.label;
                                auswahl.appendChild(option);
                            });
                        }

                        function showEditor(index = null) {
                            editor.style.display = 'block';
                            if (index === null) {
                                auswahl.value = '';
                                inputLabel.value = '';
                                inputStil.value = '';
                                document.getElementById('stilgruppe-ziel').value = '';
                                editor.dataset.index = '';
                            } else {
                                const gruppe = stilgruppen[index];
                                inputLabel.value = gruppe.label;
                                inputStil.value = gruppe.stil;
                                document.getElementById('stilgruppe-ziel').value = gruppe.ziel || '';
                                editor.dataset.index = index;
                            }

                            // Hinweistext einfügen
                            editor.querySelectorAll('p.stilgruppe-hinweis').forEach(p => p.remove());
                            document.getElementById('stilgruppe-ziel').insertAdjacentHTML('afterend', `
                            <p class="stilgruppe-hinweis" style="color: #666; font-size: 0.85em; margin-top: 8px;">
                                💡 Denk nach dem lokalen Speichern der Stilgruppe daran, auch unten auf <strong>„Einstellungen speichern“</strong> zu klicken!
                            </p>
                        `);
                        }


                        auswahl.addEventListener('change', () => {
                            const index = auswahl.value;
                            if (index !== '') {
                                showEditor(parseInt(index));
                            } else {
                                editor.style.display = 'none';
                            }
                        });

                        neueBtn.addEventListener('click', () => showEditor(null));

                        speichernBtn.addEventListener('click', () => {
                            const label = inputLabel.value.trim();
                            const stil = inputStil.value.trim();
                            const ziel = document.getElementById('stilgruppe-ziel').value.trim();
                            if (!label || !stil) return alert('Bitte fülle beide Felder aus.');

                            const index = editor.dataset.index;
                            if (index === '') {
                                stilgruppen.push({
                                    label,
                                    stil,
                                    ziel
                                });
                            } else {
                                stilgruppen[index] = {
                                    label,
                                    stil,
                                    ziel
                                };
                            }
                            updateDropdown();
                            auswahl.value = '';
                            editor.style.display = 'none';
                            syncHiddenInputs();
                        });

                        loeschenBtn.addEventListener('click', () => {
                            const index = parseInt(editor.dataset.index);
                            if (!Number.isInteger(index)) return;
                            if (!confirm('Wirklich löschen?')) return;

                            stilgruppen.splice(index, 1);
                            updateDropdown();
                            auswahl.value = '';
                            editor.style.display = 'none';
                            syncHiddenInputs();
                        });

                        function syncHiddenInputs() {
                            const form = auswahl.closest('form');
                            form.querySelectorAll('input[name="stilgruppe_label[]"], input[name="stilgruppe_stil[]"], input[name="stilgruppe_ziel[]"]').forEach(el => el.remove());
                            stilgruppen.forEach(gruppe => {
                                const input1 = document.createElement('input');
                                input1.type = 'hidden';
                                input1.name = 'stilgruppe_label[]';
                                input1.value = gruppe.label;
                                form.appendChild(input1);
                                const input2 = document.createElement('input');
                                input2.type = 'hidden';
                                input2.name = 'stilgruppe_stil[]';
                                input2.value = gruppe.stil;
                                form.appendChild(input2);
                                const input3 = document.createElement('input');
                                input3.type = 'hidden';
                                input3.name = 'stilgruppe_ziel[]';
                                input3.value = gruppe.ziel || '';
                                form.appendChild(input3);
                            });
                        }

                        updateDropdown();
                        syncHiddenInputs();
                    });
                </script>
                <tr>
                    <th scope="row"><label for="beitragseinreichung_ki_stil">Grundstil (wird an jede Stilvorgabe angehängt)</label></th>
                    <td>
                        <input type="text" name="beitragseinreichung_ki_stil" id="beitragseinreichung_ki_stil" class="regular-text" value="<?php echo esc_attr(get_option('beitragseinreichung_ki_stil', '')); ?>">
                        <p class="description">Dieser Stil wird automatisch an jede ausgewählte Stilvorgabe angehängt. Beispiel: „freundlich, sachlich, duzend“</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="beitragseinreichung_ki_modell">Modell</label></th>
                    <td>
                        <select name="beitragseinreichung_ki_modell"
                            id="beitragseinreichung_ki_modell"
                            <?php echo $ist_admin ? '' : 'disabled'; ?>
                            title="<?php echo esc_attr('Nur Admins können das Modell ändern.'); ?>">
                            <?php
                            $modelle = [
                                'gpt-4-turbo' => 'GPT-4 Turbo (schneller, günstiger, aktuelle Version)',
                                'gpt-4' => 'GPT-4 (standard, teuerer)',
                                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (günstig, aber schwächer)'
                            ];
                            $auswahl = get_option('beitragseinreichung_ki_modell', 'gpt-4-turbo');
                            foreach ($modelle as $wert => $label) {
                                echo '<option value="' . esc_attr($wert) . '" ' . selected($auswahl, $wert, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Welches OpenAI-Modell soll verwendet werden?</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Hinweis</th>
                    <td>
                        <p><strong>Modell:</strong> <span id="ki-hinweis-modell"></span></p>
                        <p><strong>Limits:</strong> Du hast ein Soft-Limit von 3 €, ein Hard-Limit von 5 € in deinem OpenAI-Konto.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="beitragseinreichung_api_key">OpenAI API-Key</label></th>
                    <td>
                        <?php if (defined('OPENAI_API_KEY')): ?>
                            <input type="text" value="(aus wp-config.php)" disabled class="regular-text">
                            <p class="description">Der API-Key wird aktuell aus der Konfiguration geladen.</p>
                        <?php else:
                            $saved_key = get_option('beitragseinreichung_api_key');
                        ?>
                            <input type="password"
                                name="beitragseinreichung_api_key"
                                id="beitragseinreichung_api_key"
                                class="regular-text"
                                value="<?php echo esc_attr(str_repeat('*', strlen($saved_key))); ?>"
                                <?php echo $ist_admin ? '' : 'disabled'; ?>
                                title="<?php echo esc_attr('Nur Admins können diesen API-Key bearbeiten.'); ?>">
                            <p class="description">Hier kannst du deinen OpenAI-API-Key sicher hinterlegen. Der Key wird nicht im Klartext angezeigt.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API-Verbindungsstatus</th>
                    <td>
                        <?php
                        $status = get_option('beitragseinreichung_api_status');
                        if (!$status) {
                            echo '<p><span style="color:gray;">Noch keine Verbindung getestet.</span></p>';
                        } else {
                            $zeit = date_i18n('d.m.Y H:i', strtotime($status['zeit']));
                            $symbol = $status['status'] === 'erfolgreich' ? '✅' : '❌';
                            $farbe = $status['status'] === 'erfolgreich' ? 'green' : 'red';
                            echo '<p><strong>Letzter Test:</strong> ' . esc_html($zeit) . '</p>';
                            echo '<p style="color:' . $farbe . ';">' . $symbol . ' ' . esc_html($status['info']) . '</p>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <button type="button" class="button" id="test-openai-verbindung">🔄 Verbindung jetzt testen</button>
                        <div id="openai-verbindungsstatus-ajax" style="margin-top:10px;"></div>
                    </td>
                </tr>
            </table>

            <?php submit_button('Einstellungen speichern'); ?>
        </form>
        <p style="margin-top: 40px; font-size: 0.95em;">
            ℹ️ <a href="https://github.com/jan-erbert/ai-beitragseinreichung/wiki" target="_blank" rel="noopener noreferrer">
                Weitere Hilfe & Dokumentation findest du im Plugin-Wiki →
            </a>
        </p>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modellSelect = document.getElementById('beitragseinreichung_ki_modell');
                const hinweisFeld = document.getElementById('ki-hinweis-modell');

                const hinweise = {
                    'gpt-4-turbo': 'GPT-4 Turbo – schnell, aktuell, günstig (ca. 1–2 Cent pro Beitrag)',
                    'gpt-4': 'GPT-4 – leistungsstark, aber teurer (ca. 4–8 Cent pro Beitrag)',
                    'gpt-3.5-turbo': 'GPT-3.5 Turbo – sehr günstig (unter 1 Cent), aber weniger präzise'
                };

                function updateHinweis() {
                    const modell = modellSelect.value;
                    hinweisFeld.textContent = hinweise[modell] || 'Unbekanntes Modell';
                }

                modellSelect.addEventListener('change', updateHinweis);
                updateHinweis(); // Initial setzen
            });
        </script>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#empfaenger_user_ids').select2({
                placeholder: 'Empfänger auswählen',
                width: 'resolve',
                closeOnSelect: false,
                templateResult: function(data) {
                    if (!data.id) return data.text;
                    const checkbox = $('<span><input type="checkbox" style="margin-right: 6px;" /> ' + data.text + '</span>');
                    return checkbox;
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('stilgruppen-container');
            const button = document.getElementById('stilgruppe-hinzufuegen');

            button.addEventListener('click', function() {
                const div = document.createElement('div');
                div.className = 'stilgruppe';
                div.style.marginBottom = '10px';
                div.innerHTML = `
                <input type="text" name="stilgruppe_label[]" placeholder="Bezeichnung (z. B. Bericht – sachlich)" style="width: 40%;" required>
                <textarea name="stilgruppe_stil[]" placeholder="Stilbeschreibung (z. B. sachlich, sportlich, informativ)" rows="14" style="width: 100%;" required></textarea>
                <button type="button" class="button stilgruppe-entfernen">–</button>
                <p style="color: #666; font-size: 0.85em; margin-top: 8px;">💡 Denk nach dem lokalen Speichern der Stilgruppe daran, auch unten auf <strong>„Einstellungen speichern“</strong> zu klicken!</p>
            `;
                container.appendChild(div);
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('stilgruppe-entfernen')) {
                    e.target.parentElement.remove();
                }
            });
        });
    </script>
    <script>
        jQuery(document).ready(function($) {
            $('#test-openai-verbindung').on('click', function() {
                const statusDiv = $('#openai-verbindungsstatus-ajax');
                statusDiv.html('🔄 Verbindung wird getestet...');

                $.post(ajaxurl, {
                    action: 'beitragseinreichung_test_openai_jetzt',
                    _wpnonce: '<?php echo wp_create_nonce('test_openai_ajax'); ?>'
                }, function(response) {
                    if (response.success) {
                        statusDiv.html('<span style="color:green;">✅ ' + response.data + '</span>');
                    } else {
                        statusDiv.html('<span style="color:red;">❌ ' + response.data + '</span>');
                    }
                });
            });
        });
    </script>
<?php

}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $links[] = '<a href="' . admin_url('admin.php?page=beitragseinreichung_einstellungen') . '">⚙️ Einstellungen</a>';
    return $links;
});

function beitrag_ki_admin_benachrichtigen($fehlermeldung)
{
    $admin_email = get_option('admin_email');
    $benutzer = wp_get_current_user();
    $zeit = current_time('mysql');

    $betreff = '❌ Fehler bei der KI-Optimierung im Plugin';
    $nachricht = "Es ist ein Fehler bei der KI-Optimierung aufgetreten.\n\n";
    $nachricht .= "Fehler: $fehlermeldung\n";
    $nachricht .= "Benutzer: {$benutzer->user_login}\n";
    $nachricht .= "Zeitpunkt: $zeit\n\n";
    $nachricht .= "Bitte prüfe die Server-Logs oder die API-Verbindung.";

    wp_mail($admin_email, $betreff, $nachricht);
}
