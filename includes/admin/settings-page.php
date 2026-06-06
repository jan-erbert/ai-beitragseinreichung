<?php

defined('ABSPATH') || exit;

/**
 * Zeigt und verarbeitet die Einstellungsseite.
 */
function beitragseinreichung_einstellungen_anzeige()
{
    $ist_admin = current_user_can('beitragseinreichung_admin');
    if (isset($_POST['beitrag_einstellungen_nonce']) && wp_verify_nonce($_POST['beitrag_einstellungen_nonce'], 'speichern_beitrag_einstellungen')) {
        if (!current_user_can('beitragseinreichung_settings')) {
            wp_die(__('Du hast keine Berechtigung, diese Einstellungen zu speichern.'));
        }

        $kategorie = isset($_POST['standard_kategorie']) ? [(int) $_POST['standard_kategorie']] : [];
        $stilgruppen = [];
        if (!empty($_POST['stilgruppe_label']) && !empty($_POST['stilgruppe_stil'])) {
            foreach ($_POST['stilgruppe_label'] as $i => $label) {
                $label = sanitize_text_field(wp_unslash($label));
                $stil = sanitize_textarea_field(wp_unslash($_POST['stilgruppe_stil'][$i] ?? ''));
                if (!empty($label) && !empty($stil)) {
                    $ziel = sanitize_text_field(wp_unslash($_POST['stilgruppe_ziel'][$i] ?? ''));
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

            if (isset($_POST['beitragseinreichung_ki_aktiv'])) {
                update_option('beitragseinreichung_ki_aktiv', (int) $_POST['beitragseinreichung_ki_aktiv']);
            }

            if (isset($_POST['beitragseinreichung_ki_modell'])) {
                $ki_modell = beitrag_normalize_ai_model(sanitize_text_field(wp_unslash($_POST['beitragseinreichung_ki_modell'])));
                update_option('beitragseinreichung_ki_modell', $ki_modell);
            }

            if (isset($_POST['beitragseinreichung_excerpt_aktiv'])) {
                update_option('beitragseinreichung_excerpt_aktiv', (int) $_POST['beitragseinreichung_excerpt_aktiv']);
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
                        const stilgruppen = <?php echo wp_json_encode(get_option('beitragseinreichung_ki_stilgruppen', [])); ?>;

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
                            $modelle = beitrag_get_ai_model_profiles();
                            $auswahl = beitrag_normalize_ai_model(get_option('beitragseinreichung_ki_modell', beitrag_get_default_ai_model()));
                            foreach ($modelle as $profil) {
                                $wert = $profil['model'];
                                $label = $profil['label'];
                                $beschreibung = $profil['description'];
                                echo '<option value="' . esc_attr($wert) . '" data-description="' . esc_attr($beschreibung) . '" ' . selected($auswahl, $wert, false) . '>' . esc_html($label) . '</option>';
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

                function updateHinweis() {
                    const option = modellSelect.options[modellSelect.selectedIndex];
                    hinweisFeld.textContent = option ? option.dataset.description : 'Unbekanntes Modell';
                }

                modellSelect.addEventListener('change', updateHinweis);
                updateHinweis(); // Initial setzen
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
                    _wpnonce: '<?php echo wp_create_nonce('test_openai_ajax'); ?>'
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
