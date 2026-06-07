<?php

defined('ABSPATH') || exit;

/**
 * Zeigt und verarbeitet die Einstellungsseite.
 */
function beitragseinreichung_einstellungen_anzeige()
{
    $ist_admin = current_user_can('beitragseinreichung_admin');
    $nonce = isset($_POST['beitrag_einstellungen_nonce']) ? sanitize_text_field(wp_unslash($_POST['beitrag_einstellungen_nonce'])) : '';
    if ($nonce && wp_verify_nonce($nonce, 'speichern_beitrag_einstellungen')) {
        if (!current_user_can('beitragseinreichung_settings')) {
            wp_die(esc_html__('Du hast keine Berechtigung, diese Einstellungen zu speichern.'));
        }

        $kategorie = isset($_POST['standard_kategorie']) ? [(int) $_POST['standard_kategorie']] : [];
        $stilgruppen = [];
        $stilgruppe_labels = isset($_POST['stilgruppe_label']) ? array_map('sanitize_text_field', wp_unslash((array) $_POST['stilgruppe_label'])) : [];
        $stilgruppe_stile = isset($_POST['stilgruppe_stil']) ? array_map('sanitize_textarea_field', wp_unslash((array) $_POST['stilgruppe_stil'])) : [];
        $stilgruppe_ziele = isset($_POST['stilgruppe_ziel']) ? array_map('sanitize_text_field', wp_unslash((array) $_POST['stilgruppe_ziel'])) : [];
        if (!empty($stilgruppe_labels) && !empty($stilgruppe_stile)) {
            foreach ($stilgruppe_labels as $i => $label) {
                $label = sanitize_text_field(wp_unslash($label));
                $stil = $stilgruppe_stile[$i] ?? '';
                if (!empty($label) && !empty($stil)) {
                    $ziel = $stilgruppe_ziele[$i] ?? '';
                    $stilgruppen[] = [
                        'label' => $label,
                        'stil' => $stil,
                        'ziel' => $ziel
                    ];
                }
            }
        }
        update_option('beitragseinreichung_ki_stilgruppen', $stilgruppen);
        $ki_stil = isset($_POST['beitragseinreichung_ki_stil']) ? sanitize_text_field(wp_unslash($_POST['beitragseinreichung_ki_stil'])) : '';
        $autor_notify = isset($_POST['beitragseinreichung_autor_notify']) ? 1 : 0;
        update_option('beitragseinreichung_autor_notify', $autor_notify);
        update_option('beitragseinreichung_ki_stil', $ki_stil);
        update_option('beitragseinreichung_standard_kategorien', $kategorie);

        if ($ist_admin) {
            $empfaenger = isset($_POST['empfaenger_user_ids']) ? array_map('intval', (array) $_POST['empfaenger_user_ids']) : [];
            update_option('beitragseinreichung_benachrichtigungs_user_ids', $empfaenger);

            $ki_aktiv = isset($_POST['beitragseinreichung_ki_aktiv']) ? (int) $_POST['beitragseinreichung_ki_aktiv'] : 0;
            update_option('beitragseinreichung_ki_aktiv', $ki_aktiv);

            if (isset($_POST['beitragseinreichung_ki_modell'])) {
                $ki_modell = beitrag_normalize_ai_model(sanitize_text_field(wp_unslash($_POST['beitragseinreichung_ki_modell'])));
                update_option('beitragseinreichung_ki_modell', $ki_modell);
            }

            if (isset($_POST['beitragseinreichung_excerpt_aktiv'])) {
                update_option('beitragseinreichung_excerpt_aktiv', (int) $_POST['beitragseinreichung_excerpt_aktiv']);
            }

            update_option('beitragseinreichung_tags_jahr_aktiv', isset($_POST['beitragseinreichung_tags_jahr_aktiv']) ? 1 : 0);
            update_option('beitragseinreichung_ki_tags_aktiv', $ki_aktiv && isset($_POST['beitragseinreichung_ki_tags_aktiv']) ? 1 : 0);
            update_option('beitragseinreichung_tags_context_aktiv', $ki_aktiv && isset($_POST['beitragseinreichung_tags_context_aktiv']) ? 1 : 0);

            if ($ki_aktiv) {
                $tag_limit = isset($_POST['beitragseinreichung_ki_tags_max']) ? (int) $_POST['beitragseinreichung_ki_tags_max'] : 5;
                update_option('beitragseinreichung_ki_tags_max', max(1, min(12, $tag_limit)));
                $tag_hints = isset($_POST['beitragseinreichung_tags_ki_hinweise']) ? sanitize_textarea_field(wp_unslash($_POST['beitragseinreichung_tags_ki_hinweise'])) : '';
                update_option('beitragseinreichung_tags_ki_hinweise', $tag_hints);
                $tag_standard_terms = isset($_POST['beitragseinreichung_tag_standard_terms'])
                    ? beitragseinreichung_parse_tags(sanitize_text_field(wp_unslash($_POST['beitragseinreichung_tag_standard_terms'])))
                    : [];
                update_option('beitragseinreichung_tag_standard_terms', $tag_standard_terms);
            }

            if (!defined('OPENAI_API_KEY') && isset($_POST['beitragseinreichung_api_key'])) {
                $saved_key = (string) get_option('beitragseinreichung_api_key', '');
                $key = trim(sanitize_text_field(wp_unslash($_POST['beitragseinreichung_api_key'])));
                $masked_key = str_repeat('*', strlen($saved_key));
                if ($key !== $masked_key) {
                    update_option('beitragseinreichung_api_key', $key);
                }
            }
        }

        echo '<div class="updated"><p>Einstellungen gespeichert.</p></div>';
        // Verbindung testen nach dem Speichern
        beitragseinreichung_test_openai_verbindung();
    }

    $standard_ids = get_option('beitragseinreichung_standard_kategorien', []);
    $user_ids = array_map('intval', (array) get_option('beitragseinreichung_benachrichtigungs_user_ids', []));

    $kategorien = get_categories(['hide_empty' => false]);
    $nutzer = get_users(['fields' => ['ID', 'display_name', 'user_email']]);
    $frequent_tags = beitragseinreichung_get_frequent_tags_with_counts(50);
    $standard_terms = beitragseinreichung_get_tag_standard_terms();
    $ki_einstellung_aktiv = (int) get_option('beitragseinreichung_ki_aktiv') === 1;
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
                                echo '<option value="' . esc_attr($kat->term_id) . '" ' . selected($standard_ids && (int) $kat->term_id === (int) $standard_ids[0], true, false) . '>' . esc_html($kat->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <?php if ($ist_admin): ?>
                    <tr>
                        <th scope="row"><label for="empfaenger_user_ids">Benachrichtigungs-Empfänger</label></th>
                        <td>
                            <div class="beitrag-user-picker" style="max-width: 720px;">
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 8px;">
                                    <input type="search" id="beitrag-user-filter" class="regular-text" placeholder="Benutzer suchen..." autocomplete="off">
                                    <button type="button" class="button" id="beitrag-user-select-visible">Sichtbare auswählen</button>
                                    <button type="button" class="button" id="beitrag-user-clear">Auswahl leeren</button>
                                    <span class="description" id="beitrag-user-count"></span>
                                </div>
                                <div id="beitrag-user-list" style="max-height: 220px; overflow-y: auto; border: 1px solid #ccd0d4; background: #fff;">
                                <?php foreach ($nutzer as $nutzer_obj): ?>
                                    <?php
                                    $suchtext = strtolower($nutzer_obj->display_name . ' ' . $nutzer_obj->user_email);
                                    ?>
                                    <label class="beitrag-user-option" data-search="<?php echo esc_attr($suchtext); ?>" style="display: flex; gap: 8px; align-items: flex-start; padding: 7px 10px; border-bottom: 1px solid #f0f0f1;">
                                        <input type="checkbox" name="empfaenger_user_ids[]" value="<?php echo esc_attr($nutzer_obj->ID); ?>"
                                            <?php checked(in_array((int) $nutzer_obj->ID, $user_ids, true)); ?>>
                                        <span>
                                            <strong><?php echo esc_html($nutzer_obj->display_name); ?></strong><br>
                                            <span class="description"><?php echo esc_html($nutzer_obj->user_email); ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="description">Wähle einen oder mehrere Benutzer aus, die bei neuen Beiträgen benachrichtigt werden sollen.</p>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const filter = document.getElementById('beitrag-user-filter');
                                    const options = Array.from(document.querySelectorAll('.beitrag-user-option'));
                                    const count = document.getElementById('beitrag-user-count');
                                    const selectVisible = document.getElementById('beitrag-user-select-visible');
                                    const clear = document.getElementById('beitrag-user-clear');

                                    function updateList() {
                                        const query = filter.value.trim().toLowerCase();
                                        let visible = 0;
                                        let checked = 0;

                                        options.forEach(option => {
                                            const isVisible = !query || option.dataset.search.includes(query);
                                            option.style.display = isVisible ? 'flex' : 'none';
                                            if (isVisible) {
                                                visible++;
                                            }
                                            if (option.querySelector('input').checked) {
                                                checked++;
                                            }
                                        });

                                        count.textContent = checked + ' ausgewählt, ' + visible + ' sichtbar';
                                    }

                                    filter.addEventListener('input', updateList);
                                    options.forEach(option => {
                                        option.querySelector('input').addEventListener('change', updateList);
                                    });
                                    selectVisible.addEventListener('click', function() {
                                        options.forEach(option => {
                                            if (option.style.display !== 'none') {
                                                option.querySelector('input').checked = true;
                                            }
                                        });
                                        updateList();
                                    });
                                    clear.addEventListener('click', function() {
                                        options.forEach(option => {
                                            option.querySelector('input').checked = false;
                                        });
                                        updateList();
                                    });

                                    updateList();
                                });
                            </script>
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
                <?php if ($ist_admin): ?>
                    <tr>
                        <th scope="row">Schlagwörter</th>
                        <td>
                            <fieldset style="max-width: 780px;">
                                <label>
                                    <input type="checkbox" name="beitragseinreichung_tags_jahr_aktiv" value="1" <?php checked(get_option('beitragseinreichung_tags_jahr_aktiv'), 1); ?>>
                                    Aktuelles Jahr automatisch als Schlagwort vorschlagen
                                </label>
                                <p class="description">Das Jahr wird im Formular als Kachel vorgeschlagen und kann fuer den einzelnen Beitrag entfernt werden.</p>

                                <div class="notice notice-warning inline" id="beitrag-ki-tags-disabled-notice" style="margin: 14px 0 12px; <?php echo $ki_einstellung_aktiv ? 'display:none;' : ''; ?>">
                                    <p><strong>KI ist deaktiviert.</strong> KI-Schlagwörter, Kontext und Hinweise sind vorbereitet sichtbar, aber erst nach Aktivierung der KI nutzbar.</p>
                                </div>

                                <fieldset id="beitrag-ki-tags-options" <?php disabled(!$ki_einstellung_aktiv); ?> style="border:0;margin:0;padding:0;">
                                    <label>
                                        <input type="checkbox" name="beitragseinreichung_ki_tags_aktiv" value="1" <?php checked(get_option('beitragseinreichung_ki_tags_aktiv'), 1); ?>>
                                        KI-Schlagwörter vorschlagen
                                    </label>
                                    <p class="description">Bei aktivierter KI kann die Vorschau passende Schlagwörter ergänzen.</p>

                                    <label for="beitragseinreichung_ki_tags_max">Maximale KI-Schlagwörter</label><br>
                                    <input type="number" min="1" max="12" name="beitragseinreichung_ki_tags_max" id="beitragseinreichung_ki_tags_max" value="<?php echo esc_attr(beitragseinreichung_get_ai_tag_limit()); ?>" style="width: 90px;">

                                    <p style="margin-top: 14px;">
                                        <label>
                                            <input type="checkbox" name="beitragseinreichung_tags_context_aktiv" value="1" <?php checked(get_option('beitragseinreichung_tags_context_aktiv'), 1); ?>>
                                            Häufig verwendete Schlagwörter als Orientierung für die KI verwenden
                                        </label>
                                    </p>

                                    <div class="beitrag-settings-tag-section beitrag-settings-tag-pool">
                                        <div class="beitrag-settings-tag-section__header">
                                            <p class="beitrag-settings-tag-section__title"><strong>Bevorzugte Schlagwörter für die KI</strong></p>
                                            <button type="button" class="button button-small" id="beitrag-settings-frequent-tags-open">Aus häufig verwendeten Schlagwörtern hinzufügen</button>
                                        </div>
                                        <div class="beitrag-settings-tag-editor" data-initial-tags="<?php echo esc_attr(wp_json_encode($standard_terms)); ?>">
                                            <div id="beitrag-settings-tag-pool-chips" class="beitrag-settings-tag-chips"></div>
                                            <input type="text" id="beitrag-settings-tag-pool-input" class="regular-text" placeholder="Schlagwort hinzufügen und Enter drücken">
                                        </div>
                                        <input type="hidden" name="beitragseinreichung_tag_standard_terms" id="beitragseinreichung_tag_standard_terms" value="<?php echo esc_attr(implode(', ', $standard_terms)); ?>">
                                        <p class="description">Diese Kacheln sind bevorzugte Schreibweisen für die KI. Löschen entfernt sie nur aus diesem KI-Pool, nicht aus WordPress.</p>
                                    </div>

                                        <p>
                                            <label for="beitragseinreichung_tags_ki_hinweise"><strong>Hinweise für KI-Schlagwörter</strong></label><br>
                                            <textarea name="beitragseinreichung_tags_ki_hinweise" id="beitragseinreichung_tags_ki_hinweise" rows="4" class="large-text" placeholder="Wenn möglich den Ort aufnehmen. Keine zu allgemeinen Tags verwenden."><?php echo esc_textarea(get_option('beitragseinreichung_tags_ki_hinweise', '')); ?></textarea>
                                        </p>

                                        <div class="beitrag-settings-tag-modal" id="beitrag-settings-frequent-tags-modal" role="dialog" aria-modal="true" aria-labelledby="beitrag-settings-frequent-tags-title" hidden>
                                            <div class="beitrag-settings-tag-modal__panel">
                                                <button type="button" class="beitrag-settings-tag-modal__close" aria-label="Popup schließen">×</button>
                                                <h2 id="beitrag-settings-frequent-tags-title">Häufig verwendete Schlagwörter</h2>
                                                <p class="description">Mit + übernimmst du ein häufig verwendetes WordPress-Schlagwort in den KI-Pool. Bereits übernommene Tags sind grün markiert.</p>
                                                <div class="beitrag-settings-tag-cloud">
                                                    <?php if (empty($frequent_tags)): ?>
                                                        <span class="description">Noch keine Schlagwörter vorhanden.</span>
                                                    <?php else: ?>
                                                        <?php foreach ($frequent_tags as $tag): ?>
                                                            <button type="button" class="beitrag-settings-frequent-tag" data-tag="<?php echo esc_attr($tag['name']); ?>" title="In den KI-Pool übernehmen">
                                                                <span><?php echo esc_html($tag['name']); ?></span>
                                                                <span class="beitrag-settings-frequent-tag__count"><?php echo esc_html((string) $tag['count']); ?></span>
                                                                <span class="beitrag-settings-frequent-tag__add" aria-hidden="true">+</span>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                </fieldset>
                            </fieldset>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row">Stilgruppen verwalten</th>
                    <td>
                        <select id="stilgruppe-auswahl" style="width: 300px;">
                            <option value="">-- Stilgruppe auswählen --</option>
                        </select>
                        <button type="button" class="button" id="neue-stilgruppe">+ Neue Stilgruppe</button>

                        <div class="beitrag-settings-style-modal" id="stilgruppe-editor" role="dialog" aria-modal="true" aria-labelledby="stilgruppe-editor-title" hidden>
                            <div class="beitrag-settings-style-modal__panel">
                                <button type="button" class="beitrag-settings-style-modal__close" aria-label="Popup schließen">×</button>
                                <h2 id="stilgruppe-editor-title">Stilgruppe bearbeiten</h2>
                                <p class="description">Speichern übernimmt die Stilgruppe und speichert direkt die kompletten Einstellungen.</p>

                                <p>
                                    <label for="stilgruppe-label"><strong>Bezeichnung</strong></label><br>
                                    <input type="text" id="stilgruppe-label" class="regular-text">
                                </p>

                                <p>
                                    <label for="stilgruppe-stil"><strong>Stilbeschreibung</strong></label><br>
                                    <textarea id="stilgruppe-stil" rows="12" class="large-text"></textarea>
                                </p>

                                <p>
                                    <label for="stilgruppe-ziel"><strong>Ziel (optional)</strong></label><br>
                                    <input type="text" id="stilgruppe-ziel" class="regular-text">
                                </p>

                                <div class="beitrag-settings-style-modal__actions">
                                    <button type="button" class="button button-link-delete" id="stilgruppe-loeschen">Löschen</button>
                                    <button type="button" class="button" id="stilgruppe-abbrechen">Abbrechen</button>
                                    <button type="button" class="button button-primary" id="stilgruppe-speichern">Speichern</button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="stilgruppe_label[]">
                        <input type="hidden" name="stilgruppe_stil[]">
                    </td>
                </tr>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const stilgruppen = <?php echo wp_json_encode(get_option('beitragseinreichung_ki_stilgruppen', [])); ?>;

                        const auswahl = document.getElementById('stilgruppe-auswahl');
                        const editor = document.getElementById('stilgruppe-editor');
                        const inputLabel = document.getElementById('stilgruppe-label');
                        const inputStil = document.getElementById('stilgruppe-stil');
                        const speichernBtn = document.getElementById('stilgruppe-speichern');
                        const loeschenBtn = document.getElementById('stilgruppe-loeschen');
                        const abbrechenBtn = document.getElementById('stilgruppe-abbrechen');
                        const schliessenBtn = editor.querySelector('.beitrag-settings-style-modal__close');
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

                        function closeEditor() {
                            editor.hidden = true;
                            auswahl.value = '';
                        }

                        function submitSettingsForm() {
                            const form = auswahl.closest('form');
                            if (form.requestSubmit) {
                                form.requestSubmit();
                                return;
                            }

                            form.submit();
                        }

                        function showEditor(index = null) {
                            editor.hidden = false;
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
                        }


                        auswahl.addEventListener('change', () => {
                            const index = auswahl.value;
                            if (index !== '') {
                                showEditor(parseInt(index));
                            } else {
                                closeEditor();
                            }
                        });

                        neueBtn.addEventListener('click', () => showEditor(null));

                        function showSettingsDialog({ title, message, confirmText = 'Weiter', cancelText = 'Abbrechen', showCancel = false }) {
                            return new Promise(resolve => {
                                const modal = document.createElement('div');
                                modal.className = 'beitrag-dialog';
                                modal.setAttribute('role', 'dialog');
                                modal.setAttribute('aria-modal', 'true');

                                const panel = document.createElement('div');
                                panel.className = 'beitrag-dialog__panel';
                                modal.appendChild(panel);

                                const close = document.createElement('button');
                                close.type = 'button';
                                close.className = 'beitrag-dialog__close';
                                close.textContent = '×';
                                close.setAttribute('aria-label', 'Hinweis schließen');
                                panel.appendChild(close);

                                const heading = document.createElement('h2');
                                heading.textContent = title;
                                panel.appendChild(heading);

                                const text = document.createElement('p');
                                text.className = 'beitrag-dialog__message';
                                text.textContent = message;
                                panel.appendChild(text);

                                const actions = document.createElement('div');
                                actions.className = 'beitrag-dialog__actions';
                                panel.appendChild(actions);

                                if (showCancel) {
                                    const cancel = document.createElement('button');
                                    cancel.type = 'button';
                                    cancel.className = 'button button-secondary beitrag-dialog__cancel';
                                    cancel.textContent = cancelText;
                                    actions.appendChild(cancel);
                                    cancel.addEventListener('click', () => closeDialog(false));
                                }

                                const confirm = document.createElement('button');
                                confirm.type = 'button';
                                confirm.className = 'button button-primary beitrag-dialog__confirm';
                                confirm.textContent = confirmText;
                                actions.appendChild(confirm);

                                function closeDialog(result) {
                                    modal.remove();
                                    resolve(result);
                                }

                                close.addEventListener('click', () => closeDialog(false));
                                confirm.addEventListener('click', () => closeDialog(true));
                                modal.addEventListener('click', event => {
                                    if (event.target === modal) {
                                        closeDialog(false);
                                    }
                                });

                                document.body.appendChild(modal);
                                confirm.focus();
                            });
                        }

                        speichernBtn.addEventListener('click', async () => {
                            const label = inputLabel.value.trim();
                            const stil = inputStil.value.trim();
                            const ziel = document.getElementById('stilgruppe-ziel').value.trim();
                            if (!label || !stil) {
                                await showSettingsDialog({
                                    title: 'Stilgruppe unvollständig',
                                    message: 'Bitte fülle Name und Stilvorgabe aus.',
                                    confirmText: 'Verstanden'
                                });
                                return;
                            }

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
                            syncHiddenInputs();
                            closeEditor();
                            submitSettingsForm();
                        });

                        loeschenBtn.addEventListener('click', async () => {
                            const index = parseInt(editor.dataset.index);
                            if (!Number.isInteger(index)) return;
                            const confirmed = await showSettingsDialog({
                                title: 'Stilgruppe löschen?',
                                message: 'Diese Stilgruppe wird aus den Einstellungen entfernt und anschließend direkt gespeichert.',
                                confirmText: 'Löschen',
                                cancelText: 'Abbrechen',
                                showCancel: true
                            });
                            if (!confirmed) return;

                            stilgruppen.splice(index, 1);
                            updateDropdown();
                            syncHiddenInputs();
                            closeEditor();
                            submitSettingsForm();
                        });

                        abbrechenBtn.addEventListener('click', closeEditor);
                        schliessenBtn.addEventListener('click', closeEditor);
                        editor.addEventListener('click', event => {
                            if (event.target === editor) {
                                closeEditor();
                            }
                        });
                        document.addEventListener('keydown', event => {
                            if (event.key === 'Escape' && !editor.hidden) {
                                closeEditor();
                            }
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
                            $modelle = beitrag_get_enabled_ai_models();
                            $auswahl = beitrag_normalize_ai_model(get_option('beitragseinreichung_ki_modell', beitrag_get_default_ai_model()));
                            foreach ($modelle as $modell => $modell_config) {
                                $label = $modell_config['label'] ?? $modell;
                                $beschreibung = $modell_config['description'] ?? '';
                                echo '<option value="' . esc_attr($modell) . '" data-description="' . esc_attr($beschreibung) . '" ' . selected($auswahl, $modell, false) . '>' . esc_html($label . ' (' . $modell . ')') . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Welche freigeschaltete OpenAI-Modellvariante soll verwendet werden?</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Hinweis</th>
                    <td>
                        <p><strong>Modell:</strong> <span id="ki-hinweis-modell"></span></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="beitragseinreichung_api_key">OpenAI API-Key</label></th>
                    <td>
                        <?php if (defined('OPENAI_API_KEY')): ?>
                            <input type="text" value="(aus wp-config.php)" disabled class="regular-text">
                            <p class="description">Der API-Key wird aktuell aus der Konfiguration geladen.</p>
                        <?php else:
                            $saved_key = (string) get_option('beitragseinreichung_api_key', '');
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
                            echo '<p style="color:' . esc_attr($farbe) . ';">' . esc_html($symbol) . ' ' . esc_html($status['info']) . '</p>';
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
        <?php if (current_user_can('beitragseinreichung_settings')): ?>
            <p style="margin-top: 18px;">
                <button type="button" class="button button-small" id="beitrag-update-popup-show">Versionshinweise noch einmal anzeigen</button>
            </p>
        <?php endif; ?>
        <p class="beitrag-plugin-version">
            AI Beitragseinreichung <?php echo esc_html('v' . BEITRAGSEINREICHUNG_VERSION); ?>
        </p>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modellSelect = document.getElementById('beitragseinreichung_ki_modell');
                const hinweisFeld = document.getElementById('ki-hinweis-modell');
                const kiSelect = document.getElementById('beitragseinreichung_ki_aktiv');
                const kiTagsOptions = document.getElementById('beitrag-ki-tags-options');
                const kiTagsNotice = document.getElementById('beitrag-ki-tags-disabled-notice');

                function updateHinweis() {
                    const option = modellSelect.options[modellSelect.selectedIndex];
                    hinweisFeld.textContent = option ? option.dataset.description : 'Unbekanntes Modell';
                }

                function updateKiTagOptions() {
                    if (!kiSelect || !kiTagsOptions) {
                        return;
                    }

                    const isKiActive = kiSelect.value === '1' && !kiSelect.disabled;
                    kiTagsOptions.disabled = !isKiActive;
                    if (kiTagsNotice) {
                        kiTagsNotice.style.display = isKiActive ? 'none' : 'block';
                    }
                }

                function normalizeSettingsTag(tag) {
                    return String(tag || '').replace(/\s+/g, ' ').replace(/^[,;\s]+|[,;\s]+$/g, '');
                }

                function settingsTagKey(tag) {
                    return normalizeSettingsTag(tag).toLocaleLowerCase();
                }

                function initSettingsTagPool() {
                    const editor = document.querySelector('.beitrag-settings-tag-editor');
                    const chips = document.getElementById('beitrag-settings-tag-pool-chips');
                    const input = document.getElementById('beitrag-settings-tag-pool-input');
                    const hidden = document.getElementById('beitragseinreichung_tag_standard_terms');
                    const frequentButtons = Array.from(document.querySelectorAll('.beitrag-settings-frequent-tag'));
                    const frequentModal = document.getElementById('beitrag-settings-frequent-tags-modal');
                    const frequentModalOpen = document.getElementById('beitrag-settings-frequent-tags-open');
                    const frequentModalClose = frequentModal ? frequentModal.querySelector('.beitrag-settings-tag-modal__close') : null;

                    if (!editor || !chips || !input || !hidden) {
                        return;
                    }

                    let tagPool = [];
                    try {
                        const initialTags = editor.dataset.initialTags ? JSON.parse(editor.dataset.initialTags) : [];
                        tagPool = Array.isArray(initialTags) ? initialTags : [];
                    } catch (error) {
                        tagPool = [];
                    }

                    function syncTagPool() {
                        hidden.value = tagPool.join(', ');
                    }

                    function updateFrequentTagStates() {
                        const tagKeys = tagPool.map(settingsTagKey);
                        frequentButtons.forEach(button => {
                            const isSelected = tagKeys.includes(settingsTagKey(button.dataset.tag || ''));
                            button.classList.toggle('beitrag-settings-frequent-tag--selected', isSelected);
                            button.setAttribute(
                                'title',
                                isSelected ? 'Bereits im KI-Pool' : 'In den KI-Pool übernehmen'
                            );
                        });
                    }

                    function renderTagPool() {
                        chips.innerHTML = '';

                        if (!tagPool.length) {
                            const placeholder = document.createElement('span');
                            placeholder.className = 'beitrag-settings-tag-chip beitrag-settings-tag-chip--placeholder';
                            placeholder.textContent = 'Noch keine bevorzugten Schlagwörter gesetzt';
                            chips.appendChild(placeholder);
                        }

                        tagPool.forEach(tag => {
                            const chip = document.createElement('span');
                            chip.className = 'beitrag-settings-tag-chip';
                            chip.textContent = tag;

                            const remove = document.createElement('button');
                            remove.type = 'button';
                            remove.className = 'beitrag-settings-tag-chip__remove';
                            remove.textContent = '×';
                            remove.setAttribute('aria-label', 'Schlagwort aus KI-Pool entfernen');
                            remove.addEventListener('click', function() {
                                removeTagFromPool(tag);
                            });

                            chip.appendChild(remove);
                            chips.appendChild(chip);
                        });

                        syncTagPool();
                        updateFrequentTagStates();
                    }

                    function addTagToPool(tag) {
                        tag = normalizeSettingsTag(tag);
                        if (!tag) {
                            return false;
                        }

                        const key = settingsTagKey(tag);
                        const exists = tagPool.some(existing => settingsTagKey(existing) === key);
                        if (exists) {
                            return false;
                        }

                        tagPool.push(tag);
                        renderTagPool();
                        return true;
                    }

                    function removeTagFromPool(tag) {
                        const key = settingsTagKey(tag);
                        tagPool = tagPool.filter(existing => settingsTagKey(existing) !== key);
                        renderTagPool();
                    }

                    function addTagsFromInput(value) {
                        String(value || '').split(',').forEach(addTagToPool);
                    }

                    input.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter' || event.key === ',') {
                            event.preventDefault();
                            addTagsFromInput(input.value);
                            input.value = '';
                        }
                    });

                    input.addEventListener('blur', function() {
                        addTagsFromInput(input.value);
                        input.value = '';
                    });

                    frequentButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            addTagToPool(button.dataset.tag || '');
                        });
                    });

                    if (frequentModal && frequentModalOpen) {
                        frequentModalOpen.addEventListener('click', function() {
                            frequentModal.hidden = false;
                        });

                        if (frequentModalClose) {
                            frequentModalClose.addEventListener('click', function() {
                                frequentModal.hidden = true;
                            });
                        }

                        frequentModal.addEventListener('click', function(event) {
                            if (event.target === frequentModal) {
                                frequentModal.hidden = true;
                            }
                        });

                        document.addEventListener('keydown', function(event) {
                            if (event.key === 'Escape' && !frequentModal.hidden) {
                                frequentModal.hidden = true;
                            }
                        });
                    }

                    tagPool = tagPool.map(normalizeSettingsTag).filter(Boolean);
                    renderTagPool();
                }

                modellSelect.addEventListener('change', updateHinweis);
                updateHinweis(); // Initial setzen
                if (kiSelect) {
                    kiSelect.addEventListener('change', updateKiTagOptions);
                    updateKiTagOptions();
                }
                initSettingsTagPool();
            });
        </script>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#test-openai-verbindung').on('click', function() {
                const statusDiv = $('#openai-verbindungsstatus-ajax');
                statusDiv.html('🔄 Verbindung wird getestet...');

                $.post(ajaxurl, {
                    action: 'beitragseinreichung_test_openai_jetzt',
                    _wpnonce: '<?php echo esc_js(wp_create_nonce('test_openai_ajax')); ?>'
                }, function(response) {
                    const color = response.success ? 'green' : 'red';
                    const icon = response.success ? '✅ ' : '❌ ';
                    const text = icon + response.data;

                    if (response.success) {
                        statusDiv.empty().append($('<span>').css('color', color).text(text));
                    } else {
                        statusDiv.empty().append($('<span>').css('color', color).text(text));
                    }
                });
            });
        });
    </script>
<?php

}
