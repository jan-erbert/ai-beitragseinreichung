<?php

/**
 * Plugin Name: 🧠 AI Beitragseinreichung
 * Plugin URI: https://jan-erbert.de
 * Description: Ermöglicht es berechtigten Nutzern, im Backend Beiträge mit Bild und Schlagwörtern einzureichen. Beiträge werden mit Status "In Verarbeitung" gespeichert und können durch AI verbessert werden.
 * Version: 1.2.0
 * Author: Jan Erbert
 * License: GPL2+
 */

defined('ABSPATH') || exit;

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
    if (!current_user_can('beitragseinreichung_settings')) {
        wp_send_json_error('Keine Berechtigung');
    }

    check_ajax_referer('test_openai_ajax');

    $status = beitragseinreichung_test_openai_verbindung();

    if ($status['status'] === 'erfolgreich') {
        wp_send_json_success($status['info']);
    } else {
        wp_send_json_error($status['info']);
    }
});

require_once plugin_dir_path(__FILE__) . 'includes/bootstrap.php';

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
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'toplevel_page_beitragseinreichung') return;

    echo '<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>';
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
