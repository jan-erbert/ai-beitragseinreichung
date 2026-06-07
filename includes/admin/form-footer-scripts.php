<?php

defined('ABSPATH') || exit;

/**
 * Gibt Formular-JavaScript fuer Media Picker, Validierung und Overlays aus.
 */
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'toplevel_page_beitragseinreichung') {
        return;
    }

    $plugin_url = plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php');
    $form_url = admin_url('admin.php?page=beitragseinreichung');
    $post_url = admin_url('post.php');

?>
    <script>
        jQuery(document).ready(function($) {
            let frame_featured, frame_gallery;

            $('#beitrag_ki_individuell').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#ki-excerpt-option').show();
                    $('#beitrag_excerpt_auto').prop('checked', true);
                    $('#ki-optionen-container').show();
                } else {
                    $('#ki-excerpt-option').hide();
                    $('#beitrag_excerpt_auto').prop('checked', false);
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

            if ($('#beitrag_ki_individuell').is(':checked')) {
                $('#ki-optionen-container').show();
                $('#ki-excerpt-option').show();
                $('#beitrag_excerpt_auto').prop('checked', true);
            }

            function markPreviewDirty() {
                $('#beitrag_preview_ready').val('0');
                $('#beitrag_preview_title').val('');
                $('#beitrag_preview_content').val('');
                $('#beitrag_preview_excerpt').val('');
                $('#beitrag_preview_original_title').val('');
                $('#beitrag_preview_original_content').val('');
                $('#beitrag_preview_ki_active').val('0');
                $('#beitrag_preview_model').val('');
                $('#beitrag_preview_ai_hint').val('');
                $('#beitrag_preview_style_group').val('');
                $('#beitrag-preview-button').prop('hidden', false);
            }

            function validateSubmissionForm() {
                if ($('#beitrag_ki_individuell').is(':checked') && !$('#beitrag_ki_stilgruppe').val()) {
                    alert('Bitte wähle einen Stil aus der Liste.');
                    return false;
                }

                const title = $('#beitrag_titel').val().trim();
                const content = $('#beitrag_inhalt').val().trim();
                const tags = $('#beitrag_tags').val().trim();

                if (!title || !content || !tags) {
                    alert('Bitte fülle alle Pflichtfelder aus: Titel, Inhalt und Schlagwörter.');
                    return false;
                }

                return true;
            }

            function setPreviewLoading(isLoading, useKiLoader) {
                $('#beitrag-preview-button, #beitrag-preview-revise-button').prop('disabled', isLoading);
                if (isLoading) {
                    if (useKiLoader) {
                        $('#lottie-loader').fadeIn();
                        $('#submit-loader').hide();
                    } else {
                        $('#submit-loader').fadeIn();
                        $('#lottie-loader').hide();
                    }
                } else {
                    $('#submit-loader').fadeOut();
                    $('#lottie-loader').fadeOut();
                }
            }

            function fillPreviewFields(preview) {
                $('#beitrag_preview_ready').val('1');
                $('#beitrag_preview_title').val(preview.title || '');
                $('#beitrag_preview_content').val(preview.content || '');
                $('#beitrag_preview_excerpt').val(preview.excerpt || '');
                $('#beitrag_preview_original_title').val(preview.original_title || '');
                $('#beitrag_preview_original_content').val(preview.original_content || '');
                $('#beitrag_preview_ki_active').val(preview.ki_active ? '1' : '0');
                $('#beitrag_preview_model').val(preview.model || '');
                $('#beitrag_preview_ai_hint').val(preview.ai_hint || '');
                $('#beitrag_preview_style_group').val(preview.style_group || '');
            }

            function renderPreview(preview) {
                const metaParts = [];
                if (preview.category_name) {
                    metaParts.push('Kategorie: ' + preview.category_name);
                }
                if (preview.tags) {
                    metaParts.push('Schlagwörter: ' + preview.tags);
                }
                if (preview.ki_active) {
                    metaParts.push('KI überarbeitet');
                }

                $('#beitrag-preview-meta').text(metaParts.join(' · '));
                $('#beitrag-preview-title').text(preview.title || '');
                $('#beitrag-preview-featured-image').html(preview.featured_image_html || '');
                $('#beitrag-preview-body').html(preview.content_html || '');
                $('#beitrag-preview-gallery').html(preview.gallery_html || '');

                if (preview.excerpt) {
                    $('#beitrag-preview-excerpt').html('<p class="beitrag-preview__excerpt">' + $('<div>').text(preview.excerpt).html() + '</p>');
                } else {
                    $('#beitrag-preview-excerpt').empty();
                }

                $('#beitrag-preview-panel').prop('hidden', false);
                $('#beitrag-preview-button').prop('hidden', true);
                $('#beitrag-submit-final').prop('disabled', false);
                $('html, body').animate({
                    scrollTop: $('#beitrag-preview-panel').offset().top - 40
                }, 400);
            }

            function createPreview(includeChangeRequest) {
                if (!validateSubmissionForm()) {
                    return;
                }

                const form = document.getElementById('beitragseinreichung-formular');
                const formData = new FormData(form);
                formData.append('action', 'beitragseinreichung_preview_beitrag');
                [
                    'beitrag_preview_ready',
                    'beitrag_preview_title',
                    'beitrag_preview_content',
                    'beitrag_preview_excerpt',
                    'beitrag_preview_original_title',
                    'beitrag_preview_original_content',
                    'beitrag_preview_ki_active',
                    'beitrag_preview_model',
                    'beitrag_preview_ai_hint',
                    'beitrag_preview_style_group'
                ].forEach(function(fieldName) {
                    formData.delete(fieldName);
                });

                if (!includeChangeRequest) {
                    formData.set('beitrag_preview_change_request', '');
                }

                setPreviewLoading(true, $('#beitrag_ki_individuell').is(':checked'));

                window.fetch(window.ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                    .then(response => response.json())
                    .then(result => {
                        if (!result.success) {
                            const message = result.data && result.data.message ? result.data.message : 'Die Vorschau konnte nicht erstellt werden.';
                            alert(message);
                            return;
                        }

                        fillPreviewFields(result.data.preview);
                        renderPreview(result.data.preview);
                    })
                    .catch(() => {
                        alert('Die Vorschau konnte nicht erstellt werden.');
                    })
                    .finally(() => {
                        setPreviewLoading(false, false);
                    });
            }

            $('#beitrag-preview-button').on('click', function() {
                createPreview(false);
            });

            $('#beitrag-preview-revise-button').on('click', function() {
                if (!$('#beitrag_ki_individuell').is(':checked')) {
                    alert('Bitte aktiviere die automatische Textverbesserung, um Änderungswünsche an die KI zu senden.');
                    return;
                }

                createPreview(true);
            });

            $('#beitragseinreichung-formular').on('input change', 'input, textarea, select', function(e) {
                if ($(e.target).closest('#beitrag-preview-panel').length) {
                    return;
                }

                markPreviewDirty();
            });

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
                    markPreviewDirty();
                });

                frame_featured.open();
            });

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
                    const preview = $('#gallery_preview').empty();
                    $('#gallery_ids').val(ids);
                    markPreviewDirty();

                    if (!attachments.length) {
                        $('#clear_gallery').prop('hidden', true);
                        return;
                    }

                    $('#clear_gallery').prop('hidden', false);

                    $('<p />', {
                        class: 'gallery-preview-count',
                        text: attachments.length === 1 ? '1 zusätzliches Bild ausgewählt' : attachments.length + ' zusätzliche Bilder ausgewählt'
                    }).appendTo(preview);

                    const list = $('<div />', {
                        class: 'gallery-preview-grid'
                    }).appendTo(preview);

                    attachments.forEach(function(attachment) {
                        const thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                        $('<img />', {
                            src: thumb,
                            alt: attachment.alt || attachment.title || 'Ausgewähltes Zusatzbild'
                        }).appendTo(list);
                    });

                    if (attachments.length > 0) {
                        $('<p />', {
                            class: 'gallery-preview-hint',
                            text: attachments.length > 1 ?
                                'Die Bilder werden im Beitrag als Galerie-Slider eingefügt.' :
                                'Das Bild wird sauber unter dem Beitrag eingefügt.'
                        }).appendTo(preview);
                    }
                });

                frame_gallery.open();
            });

            $('#clear_gallery').on('click', function(e) {
                e.preventDefault();
                $('#gallery_ids').val('');
                $('#gallery_preview').empty();
                $('#clear_gallery').prop('hidden', true);
                markPreviewDirty();

                if (frame_gallery) {
                    frame_gallery.state().get('selection').reset();
                }
            });

            $(document).on('submit', '#beitragseinreichung-formular', function(e) {
                if (!validateSubmissionForm()) {
                    e.preventDefault();
                    return;
                }

                const hasImage = $('#beitragsbild_id').val();

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
                                src="<?php echo esc_url($plugin_url . 'assets/lottie/error-animation.json'); ?>"
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
                    window.location.href = '<?php echo esc_url($form_url); ?>';
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
                                src="<?php echo esc_url($plugin_url . 'assets/lottie/success-animation.json'); ?>"
                                background="transparent"
                                speed="0.7"
                                style="width: 200px; height: 200px;"
                                autoplay>
                            </lottie-player>
                        </div>
                        <p style="margin: 15px 0 20px;">Dein Beitrag wurde gespeichert und wartet auf Prüfung.</p>
                        <div style="display: flex; gap: 20px; margin-top: 20px;">
                            <button class="button button-primary custom-hover" onclick="window.location.href='<?php echo esc_url($form_url); ?>'" style="padding: 10px 20px; font-size: 16px; border-radius: 5px; background: #2271b1; border-color: #2271b1; color: white;">Neuen Beitrag einreichen</button>
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
                        window.location.href = '<?php echo esc_url($post_url); ?>?post=' + beitragID + '&action=edit';
                    }
                });

                $('#close-success').on('click', function() {
                    $('#success-overlay').fadeOut();
                });
            }

            $(document).on('mouseenter', '.custom-hover', function() {
                $(this).css('filter', 'brightness(1.2)');
            }).on('mouseleave', '.custom-hover', function() {
                $(this).css('filter', 'brightness(1)');
            });
        });
    </script>
<?php
});
