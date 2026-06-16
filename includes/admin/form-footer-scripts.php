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
            let allowProgrammaticSubmit = false;

            function showDecisionModal(options) {
                const settings = $.extend({
                    title: 'Hinweis',
                    message: '',
                    details: [],
                    confirmText: 'Weiter',
                    cancelText: 'Abbrechen',
                    confirmClass: 'button-primary',
                    showCancel: true
                }, options || {});

                return new Promise(resolve => {
                    const modal = $('<div />', {
                        class: 'beitrag-dialog',
                        role: 'dialog',
                        'aria-modal': 'true',
                        'aria-labelledby': 'beitrag-dialog-title'
                    });
                    const panel = $('<div />', {
                        class: 'beitrag-dialog__panel'
                    }).appendTo(modal);

                    $('<button />', {
                        type: 'button',
                        class: 'beitrag-dialog__close',
                        text: '×',
                        'aria-label': 'Hinweis schließen'
                    }).appendTo(panel);

                    $('<h2 />', {
                        id: 'beitrag-dialog-title',
                        text: settings.title
                    }).appendTo(panel);

                    if (settings.message) {
                        $('<p />', {
                            class: 'beitrag-dialog__message',
                            text: settings.message
                        }).appendTo(panel);
                    }

                    const details = settings.details.filter(Boolean);
                    if (details.length) {
                        const list = $('<ul />', {
                            class: 'beitrag-dialog__list'
                        }).appendTo(panel);
                        details.forEach(detail => {
                            $('<li />', {
                                text: detail
                            }).appendTo(list);
                        });
                    }

                    const actions = $('<div />', {
                        class: 'beitrag-dialog__actions'
                    }).appendTo(panel);

                    if (settings.showCancel) {
                        $('<button />', {
                            type: 'button',
                            class: 'button button-secondary beitrag-dialog__cancel',
                            text: settings.cancelText
                        }).appendTo(actions);
                    }

                    $('<button />', {
                        type: 'button',
                        class: 'button ' + settings.confirmClass + ' beitrag-dialog__confirm',
                        text: settings.confirmText
                    }).appendTo(actions);

                    function close(result) {
                        modal.remove();
                        resolve(result);
                    }

                    modal.on('click', function(event) {
                        if (event.target === modal[0]) {
                            close(false);
                        }
                    });
                    modal.find('.beitrag-dialog__close, .beitrag-dialog__cancel').on('click', function() {
                        close(false);
                    });
                    modal.find('.beitrag-dialog__confirm').on('click', function() {
                        close(true);
                    });

                    $('body').append(modal);
                    modal.find('.beitrag-dialog__confirm').trigger('focus');
                });
            }

            function showInfoModal(title, message, details) {
                return showDecisionModal({
                    title: title,
                    message: message,
                    details: details || [],
                    confirmText: 'Verstanden',
                    showCancel: false
                });
            }

            $('#beitrag_ki_individuell').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#ki-excerpt-option').show();
                    $('#beitrag_excerpt_auto').prop('checked', true);
                    $('#ki-tags-option').show();
                    $('#beitrag_ki_tags_auto').prop('checked', true);
                    $('#ki-optionen-container').show();
                    $('#beitrag-ai-enabled-animation').prop('hidden', false);
                } else {
                    $('#ki-excerpt-option').hide();
                    $('#beitrag_excerpt_auto').prop('checked', false);
                    $('#ki-tags-option').hide();
                    $('#beitrag_ki_tags_auto').prop('checked', false);
                    $('#ki-optionen-container').hide();
                    $('#beitrag-ai-enabled-animation').prop('hidden', true);
                }
                updateTagMode();
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
            $('#beitrag_ki_tags_auto').on('change', updateTagMode);

            if ($('#beitrag_ki_individuell').is(':checked')) {
                $('#ki-optionen-container').show();
                $('#ki-excerpt-option').show();
                $('#beitrag_excerpt_auto').prop('checked', true);
                $('#ki-tags-option').show();
                $('#beitrag_ki_tags_auto').prop('checked', true);
                $('#beitrag-ai-enabled-animation').prop('hidden', false);
            }

            const tagState = [];
            let isApplyingPreviewTags = false;

            function normalizeTag(tag) {
                return String(tag || '').replace(/\s+/g, ' ').replace(/^[,;\s]+|[,;\s]+$/g, '');
            }

            function tagKey(tag) {
                return normalizeTag(tag).toLocaleLowerCase();
            }

            function syncTags() {
                $('#beitrag_tags').val(tagState.join(', '));
            }

            function renderTags() {
                const chips = $('#beitrag-tag-chips').empty();
                if (!tagState.length && $('#beitrag_ki_individuell').is(':checked')) {
                    $('<span />', {
                        class: 'beitrag-tag-chip beitrag-tag-chip--placeholder',
                        text: 'Noch keine KI-Schlagwörter erstellt'
                    }).appendTo(chips);
                }
                tagState.forEach(function(tag) {
                    const chip = $('<span />', {
                        class: 'beitrag-tag-chip',
                        text: tag
                    });
                    $('<button />', {
                        type: 'button',
                        class: 'beitrag-tag-chip__remove',
                        text: '×',
                        'aria-label': 'Schlagwort entfernen'
                    }).on('click', function() {
                        removeTag(tag);
                    }).appendTo(chip);
                    chips.append(chip);
                });
                syncTags();
            }

            function updateTagMode() {
                const editorWrap = $('#beitrag-tag-editor-wrap');
                const tagInput = $('#beitrag_tag_input');
                const kiTagsSlot = $('#beitrag-ki-tags-slot');
                const aiTagsEnabled = String(editorWrap.data('ai-tags-enabled')) === '1';
                const isKiActive = $('#beitrag_ki_individuell').is(':checked') && aiTagsEnabled && $('#beitrag_ki_tags_auto').is(':checked');

                if (!editorWrap.length) {
                    return;
                }

                if (isKiActive && kiTagsSlot.length) {
                    kiTagsSlot.prop('hidden', false).append(editorWrap);
                    $('#beitrag-tags-row').hide();
                    editorWrap.addClass('beitrag-tag-editor-wrap--ki');
                    $('.beitrag-tag-editor').addClass('beitrag-tag-editor--locked');
                    tagInput.prop('disabled', true).val('');
                    $('#tag-hinweis').hide();
                    renderTags();
                    return;
                }

                $('#beitrag-tags-default-slot').append(editorWrap);
                $('#beitrag-tags-row').show();
                kiTagsSlot.prop('hidden', true);
                editorWrap.removeClass('beitrag-tag-editor-wrap--ki');
                $('.beitrag-tag-editor').removeClass('beitrag-tag-editor--locked');
                tagInput.prop('disabled', false);
                renderTags();
            }

            function addTag(tag) {
                tag = normalizeTag(tag);
                if (!tag) {
                    return false;
                }

                const key = tagKey(tag);
                const exists = tagState.some(function(existing) {
                    return tagKey(existing) === key;
                });

                if (exists) {
                    return false;
                }

                tagState.push(tag);
                renderTags();
                if (!isApplyingPreviewTags) {
                    markPreviewDirty();
                }
                return true;
            }

            function addTags(tags) {
                String(tags || '').split(',').forEach(addTag);
            }

            function removeTag(tag) {
                const key = tagKey(tag);
                const index = tagState.findIndex(function(existing) {
                    return tagKey(existing) === key;
                });

                if (index >= 0) {
                    tagState.splice(index, 1);
                    renderTags();
                    markPreviewDirty();
                }
            }

            function initTags() {
                const editor = $('.beitrag-tag-editor');
                const initialTags = editor.data('initial-tags') || [];
                initialTags.forEach(function(tag) {
                    if (normalizeTag(tag)) {
                        tagState.push(normalizeTag(tag));
                    }
                });
                renderTags();
            }

            initTags();
            updateTagMode();

            $('#beitrag_tag_input').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addTags($(this).val());
                    $(this).val('');
                }
            }).on('blur', function() {
                addTags($(this).val());
                $(this).val('');
            });

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
                $('#beitrag_preview_tags').val('');
                $('#beitrag_preview_token').val('');
                $('#beitrag-preview-button').prop('hidden', false);
            }

            function getValidationError() {
                if ($('#beitrag_ki_individuell').is(':checked') && !$('#beitrag_ki_stilgruppe').val()) {
                    return 'Bitte wähle einen Stil aus der Liste.';
                }

                const title = $('#beitrag_titel').val().trim();
                const content = $('#beitrag_inhalt').val().trim();
                const tags = $('#beitrag_tags').val().trim();

                const aiTagsEnabled = String($('#beitrag-tag-editor-wrap').data('ai-tags-enabled')) === '1';
                const aiTagsActive = $('#beitrag_ki_individuell').is(':checked') && aiTagsEnabled && $('#beitrag_ki_tags_auto').is(':checked');

                if (!title || !content || (!aiTagsActive && !tags)) {
                    return 'Bitte fülle alle Pflichtfelder aus: Titel, Inhalt und Schlagwörter.';
                }

                return '';
            }

            function getSubmissionWarnings() {
                const warnings = [];

                if (!$('#beitragsbild_id').val()) {
                    warnings.push('Du hast kein Beitragsbild ausgewählt. Das Beitragsbild ist wichtig für die Darstellung und wird empfohlen.');
                }

                if (!$('#beitrag_ki_individuell').is(':checked')) {
                    warnings.push('Du hast die automatische Textverbesserung nicht aktiviert. Der Beitrag könnte dadurch an Qualität und Stil verlieren.');
                }

                return warnings;
            }

            function getDirectSubmitWarnings() {
                const warnings = getSubmissionWarnings();
                const hasPreview = $('#beitrag_preview_ready').val() === '1';

                if (!hasPreview) {
                    warnings.unshift('Du hast noch keine Vorschau erstellt. Der Beitrag wird direkt gespeichert, ohne dass du Titel, Text, Bilder und Schlagwörter vorher im Plugin prüfen konntest.');
                }

                if (!hasPreview && $('#beitrag_ki_individuell').is(':checked')) {
                    warnings.push('KI kann Fehler machen oder Formulierungen unpassend gewichten. Prüfe den eingereichten Beitrag anschließend im Editor.');
                }

                return warnings;
            }

            function confirmSubmissionWarnings(actionText) {
                const warnings = getSubmissionWarnings();
                if (!warnings.length) {
                    return Promise.resolve(true);
                }

                return showDecisionModal({
                    title: 'Hinweis vor der ' + actionText,
                    message: 'Bitte prüfe kurz, ob du trotzdem fortfahren möchtest.',
                    details: warnings,
                    confirmText: 'Trotzdem fortfahren',
                    cancelText: 'Zurück zum Formular'
                });
            }

            function setLoadingText(message) {
                $('#submit-loader-text').text(message);
                $('#lottie-loader-text').text('⏳ ' + message);
            }

            function setPreviewLoading(isLoading, useKiLoader, message) {
                $('#beitrag-preview-button, #beitrag-preview-revise-button').prop('disabled', isLoading);
                if (isLoading) {
                    setLoadingText(message || 'Deine Vorschau wird erstellt …');
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
                $('#beitrag_preview_token').val(preview.token || '');
                if (preview.tags) {
                    isApplyingPreviewTags = true;
                    addTags(preview.tags);
                    isApplyingPreviewTags = false;
                    $('#beitrag_preview_tags').val($('#beitrag_tags').val());
                }
            }

            function renderPreview(preview) {
                const metaParts = [];
                if (preview.category_name) {
                    metaParts.push('Kategorie: ' + preview.category_name);
                }
                if (preview.ki_active) {
                    metaParts.push('KI überarbeitet');
                }

                $('#beitrag-preview-meta').text(metaParts.join(' · '));
                const previewTags = String(preview.tags || '')
                    .split(',')
                    .map(tag => tag.trim())
                    .filter(Boolean);

                const previewTagBox = $('#beitrag-preview-tags').empty();
                if (previewTags.length) {
                    $('<div />', {
                        class: 'beitrag-preview__tags-label',
                        text: 'Schlagwörter'
                    }).appendTo(previewTagBox);

                    const previewTagList = $('<div />', {
                        class: 'beitrag-preview__tags-list'
                    }).appendTo(previewTagBox);

                    previewTags.forEach(function(tag) {
                        $('<span />', {
                            class: 'beitrag-preview__tag',
                            text: tag
                        }).appendTo(previewTagList);
                    });
                    previewTagBox.prop('hidden', false);
                } else {
                    previewTagBox.prop('hidden', true);
                }

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

            async function createPreview(includeChangeRequest) {
                const validationError = getValidationError();
                if (validationError) {
                    await showInfoModal('Eingaben unvollständig', validationError);
                    return;
                }
                if (!includeChangeRequest && !(await confirmSubmissionWarnings('Vorschau-Erstellung'))) {
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
                    'beitrag_preview_style_group',
                    'beitrag_preview_tags',
                    'beitrag_preview_token'
                ].forEach(function(fieldName) {
                    formData.delete(fieldName);
                });

                if (!includeChangeRequest) {
                    formData.set('beitrag_preview_change_request', '');
                }

                setPreviewLoading(
                    true,
                    $('#beitrag_ki_individuell').is(':checked'),
                    includeChangeRequest ? 'Deine Vorschau wird überarbeitet …' : 'Deine Vorschau wird erstellt …'
                );

                window.fetch(window.ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                    .then(response => response.json())
                    .then(result => {
                        if (!result.success) {
                            const message = result.data && result.data.message ? result.data.message : 'Die Vorschau konnte nicht erstellt werden.';
                            showInfoModal('Vorschau nicht erstellt', message);
                            return;
                        }

                        fillPreviewFields(result.data.preview);
                        renderPreview(result.data.preview);
                    })
                    .catch(() => {
                        showInfoModal('Vorschau nicht erstellt', 'Die Vorschau konnte nicht erstellt werden.');
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
                    showInfoModal('KI nicht aktiviert', 'Bitte aktiviere die automatische Textverbesserung, um Änderungswünsche an die KI zu senden.');
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

            $(document).on('submit', '#beitragseinreichung-formular', async function(e) {
                if (allowProgrammaticSubmit) {
                    allowProgrammaticSubmit = false;
                    return;
                }

                e.preventDefault();

                const validationError = getValidationError();
                if (validationError) {
                    await showInfoModal('Eingaben unvollständig', validationError);
                    return;
                }

                const warnings = getDirectSubmitWarnings();
                const confirmed = await showDecisionModal({
                    title: $('#beitrag_preview_ready').val() === '1' ? 'Beitrag einreichen?' : 'Ohne Vorschau einreichen?',
                    message: warnings.length ? 'Bitte lies die Hinweise, bevor du den Beitrag speicherst.' : 'Der Beitrag wird jetzt gespeichert und zur Prüfung abgelegt.',
                    details: warnings,
                    confirmText: 'Beitrag einreichen',
                    cancelText: 'Zurück zum Formular'
                });

                if (!confirmed) {
                    return;
                }

                if ($('#beitrag_ki_individuell').is(':checked')) {
                    setLoadingText('Dein Beitrag wird eingereicht ...');
                    $('#lottie-loader').fadeIn();
                    $('#submit-loader').hide();
                    $('html, body').animate({
                        scrollTop: $('#lottie-loader').offset().top - 40
                    }, 500);
                } else {
                    setLoadingText('Dein Beitrag wird eingereicht ...');
                    $('#submit-loader').fadeIn();
                    $('#lottie-loader').hide();
                    $('html, body').animate({
                        scrollTop: $('#submit-loader').offset().top - 40
                    }, 500);
                }

                allowProgrammaticSubmit = true;
                this.submit();
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
