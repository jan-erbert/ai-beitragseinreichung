<?php

defined('ABSPATH') || exit;

/**
 * Zeigt das Beitragseinreichungsformular an.
 */
function beitragseinreichung_formular_anzeige()
{
    $excerpt_aktiv = get_option('beitragseinreichung_excerpt_aktiv', 1);
    $ki_global_aktiv = get_option('beitragseinreichung_ki_aktiv');
?>
    <div class="wrap">

        <picture id="beitragseinreichung-logo">
            <source srcset="<?php echo plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php') . 'img/banner-small.png'; ?>" media="(max-width: 768px)">
            <img src="<?php echo plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php') . 'img/banner-big.png'; ?>" alt="AI Beitragseinreichung Logo" style="width: 100%; max-width: 800px; height: auto;">
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
                                <p class="description">Wenn aktiviert, werden Titel und Inhalt dieses Beitrags mit dem gewählten KI-Modell stilistisch überarbeitet.</p>
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
                    src="<?php echo plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php') . 'assets/lottie/ki-animation.json'; ?>"
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
