<?php

defined('ABSPATH') || exit;

// 2. Nur Admins duerfen im Protokoll loeschen
add_action('wp_ajax_beitragseinreichung_ki_log_loeschen', function () {
    if (!current_user_can('beitragseinreichung_admin')) {
        wp_send_json_error('Keine Berechtigung');
    }
    check_ajax_referer('ki_log_loeschen');

    $index = isset($_POST['index']) ? (int) sanitize_text_field(wp_unslash($_POST['index'])) : -1;
    $logs = get_option('beitragseinreichung_ki_logs', []);

    if ($index >= 0 && $index < count($logs)) {
        array_splice($logs, count($logs) - 1 - $index, 1);
        update_option('beitragseinreichung_ki_logs', $logs);
        wp_send_json_success();
    }

    wp_send_json_error('Ungültiger Index');
});

/**
 * Speichert einen Eintrag im KI-Protokoll.
 */
function beitrag_ki_log_speichern($post_id, $autor_id, $original_titel, $optimierter_titel, $original_inhalt, $optimierter_inhalt, $modell, $zusatz, $stilgruppe = '')
{
    $logs = get_option('beitragseinreichung_ki_logs', []);
    $logs[] = [
        'zeit' => current_time('mysql'),
        'autor_id' => $autor_id,
        'post_id' => $post_id,
        'titel' => get_the_title($post_id),
        'original_titel' => $original_titel,
        'optimierter_titel' => $optimierter_titel,
        'excerpt' => get_post_field('post_excerpt', $post_id),
        'original_inhalt' => $original_inhalt,
        'optimierter_inhalt' => $optimierter_inhalt,
        'modell' => $modell,
        'stilgruppe' => $stilgruppe,
        'zusatz' => $zusatz,
    ];
    update_option('beitragseinreichung_ki_logs', $logs);
}

/**
 * Zeigt das KI-Protokoll im Adminbereich an.
 */
function beitragseinreichung_ki_log_anzeige()
{
    $logs = get_option('beitragseinreichung_ki_logs', []);

    echo '<div class="wrap">';
    echo '<h1>KI-Protokoll</h1>';
    echo '<p class="description">Übersicht der KI-Überarbeitungen. Details öffnen sich als Vorschau, damit die Liste übersichtlich bleibt.</p>';

    if (empty($logs)) {
        echo '<p>Keine KI-Optimierungen wurden bisher protokolliert.</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>
        <th style="width:150px;">Datum</th>
        <th>Beitrag</th>
        <th style="width:160px;">Autor</th>
        <th style="width:180px;">Stilgruppe</th>
        <th style="width:220px;">Aktionen</th>
    </tr></thead>';
    echo '<tbody>';
    $modals = '';

    foreach (array_reverse($logs) as $index => $log) {
        $autor = get_userdata($log['autor_id']);
        $post_link = get_edit_post_link($log['post_id']);
        $autor_name = $autor ? esc_html($autor->display_name) : 'Unbekannt';
        $modell = !empty($log['modell']) ? beitrag_get_ai_model_display_name($log['modell']) : 'unbekannt';
        $stilgruppe = isset($log['stilgruppe']) && trim($log['stilgruppe']) !== '' ? $log['stilgruppe'] : 'Unbekannt';
        $excerpt = isset($log['excerpt']) ? (string) $log['excerpt'] : '';
        $detail_id = 'ki-log-detail-' . (int) $index;

        echo '<tr class="ki-log-row">';
        echo '<td data-label="Datum">' . esc_html($log['zeit']) . '</td>';
        echo '<td data-label="Beitrag">';
        echo '<strong><a href="' . esc_url($post_link) . '">' . esc_html($log['titel']) . '</a></strong>';
        echo '<br><span class="description">' . esc_html($modell) . '</span>';
        echo '</td>';
        echo '<td data-label="Autor">' . esc_html($autor_name) . '</td>';
        echo '<td data-label="Stilgruppe">' . esc_html($stilgruppe) . '</td>';
        echo '<td data-label="Aktionen">';
        echo '<button class="button button-primary ki-log-open" type="button" data-target="' . esc_attr($detail_id) . '">Anzeigen</button>';
        if (current_user_can('beitragseinreichung_admin')) {
            echo ' <button class="button ki-log-delete" data-index="' . esc_attr($index) . '">Löschen</button>';
        }
        echo '</td>';
        echo '</tr>';

        $modals .= '<div class="ki-log-modal" id="' . esc_attr($detail_id) . '" hidden>';
        $modals .= '<div class="ki-log-modal__backdrop" data-close-modal="1"></div>';
        $modals .= '<div class="ki-log-modal__panel" role="dialog" aria-modal="true" aria-labelledby="' . esc_attr($detail_id) . '-title">';
        $modals .= '<button type="button" class="ki-log-modal__close" data-close-modal="1" aria-label="' . esc_attr__('Details schliessen', 'ai-beitragseinreichung') . '">×</button>';
        $modals .= '<p class="ki-log-modal__eyebrow">' . esc_html($log['zeit']) . ' · ' . esc_html($autor_name) . '</p>';
        $modals .= '<h2 id="' . esc_attr($detail_id) . '-title">' . esc_html($log['optimierter_titel']) . '</h2>';
        $modals .= '<div class="ki-log-modal__meta">';
        $modals .= '<span>Modell: ' . esc_html($modell) . '</span>';
        $modals .= '<span>Stilgruppe: ' . esc_html($stilgruppe) . '</span>';
        if ($post_link) {
            $modals .= '<a href="' . esc_url($post_link) . '">Beitrag öffnen</a>';
        }
        $modals .= '</div>';

        if ($excerpt !== '') {
            $modals .= '<div class="ki-log-modal__excerpt"><strong>Textauszug</strong><br>' . esc_html($excerpt) . '</div>';
        }

        $modals .= '<div class="ki-log-modal__grid">';
        $modals .= '<section>';
        $modals .= '<h3>Original</h3>';
        $modals .= '<h4>' . esc_html($log['original_titel']) . '</h4>';
        $modals .= '<div class="ki-log-modal__text">' . nl2br(esc_html($log['original_inhalt'])) . '</div>';
        $modals .= '</section>';
        $modals .= '<section>';
        $modals .= '<h3>Optimierte Vorschau</h3>';
        $modals .= '<h4>' . esc_html($log['optimierter_titel']) . '</h4>';
        $modals .= '<div class="ki-log-modal__text">' . beitragseinreichung_ki_log_render_content($log['optimierter_inhalt']) . '</div>';
        $modals .= '</section>';
        $modals .= '</div>';

        if (!empty($log['zusatz'])) {
            $modals .= '<div class="ki-log-modal__hint"><strong>KI-Hinweise</strong><br>' . nl2br(esc_html($log['zusatz'])) . '</div>';
        }

        $modals .= '</div>';
        $modals .= '</div>';
    }
    echo '</tbody>';
    echo '</table>';
    echo $modals; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Modal HTML is assembled from escaped values above.
    beitragseinreichung_ki_log_render_assets();
    echo '</div>';
}

/**
 * Rendert optimierten Inhalt fuer die Protokoll-Vorschau.
 */
function beitragseinreichung_ki_log_render_content($content)
{
    if (function_exists('beitragseinreichung_render_submission_preview_content')) {
        return beitragseinreichung_render_submission_preview_content($content);
    }

    return wpautop(wp_kses_post($content));
}

/**
 * Rendert Styles und JavaScript fuer das Protokoll-Modal.
 */
function beitragseinreichung_ki_log_render_assets()
{
    echo '<style>
    @media (max-width: 782px) {
        table.widefat {
            border: 0;
        }

        table.widefat thead {
            display: none;
        }

        table.widefat tbody,
        table.widefat tr,
        table.widefat td {
            display: block;
            width: 100%;
        }

        table.widefat tr.ki-log-row {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            box-sizing: border-box;
            margin: 0 0 12px;
            padding: 12px;
        }

        table.widefat tr.ki-log-row td {
            border: 0;
            box-sizing: border-box;
            padding: 8px 0;
            word-break: normal;
        }

        table.widefat tr.ki-log-row td::before {
            color: #646970;
            content: attr(data-label);
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        table.widefat tr.ki-log-row td[data-label="Aktionen"] {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        table.widefat tr.ki-log-row td[data-label="Aktionen"]::before {
            flex-basis: 100%;
        }
    }

    .ki-log-modal[hidden] {
        display: none;
    }

    .ki-log-modal {
        inset: 0;
        position: fixed;
        z-index: 100000;
    }

    .ki-log-modal__backdrop {
        background: rgba(0, 0, 0, 0.52);
        inset: 0;
        position: absolute;
    }

    .ki-log-modal__panel {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        box-sizing: border-box;
        left: 50%;
        max-height: calc(100vh - 48px);
        max-width: 1100px;
        overflow: auto;
        padding: 28px;
        position: absolute;
        top: 24px;
        transform: translateX(-50%);
        width: calc(100vw - 48px);
    }

    .ki-log-modal__close {
        background: transparent;
        border: 0;
        color: #646970;
        cursor: pointer;
        font-size: 28px;
        line-height: 1;
        padding: 8px;
        position: absolute;
        right: 12px;
        top: 10px;
    }

    .ki-log-modal__eyebrow {
        color: #646970;
        margin: 0 36px 8px 0;
    }

    .ki-log-modal h2 {
        font-size: 26px;
        line-height: 1.25;
        margin: 0 36px 14px 0;
    }

    .ki-log-modal__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 18px;
        margin-bottom: 18px;
    }

    .ki-log-modal__meta span,
    .ki-log-modal__meta a {
        background: #f6f7f7;
        border: 1px solid #dcdcde;
        border-radius: 999px;
        padding: 6px 10px;
    }

    .ki-log-modal__excerpt,
    .ki-log-modal__hint {
        background: #f6f7f7;
        border-left: 4px solid #2271b1;
        margin: 0 0 18px;
        padding: 12px 14px;
    }

    .ki-log-modal__hint {
        border-left-color: #dba617;
    }

    .ki-log-modal__grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ki-log-modal__grid section {
        border: 1px solid #dcdcde;
        border-radius: 8px;
        padding: 16px;
    }

    .ki-log-modal__grid h3 {
        margin: 0 0 10px;
    }

    .ki-log-modal__grid h4 {
        font-size: 18px;
        line-height: 1.3;
        margin: 0 0 12px;
    }

    .ki-log-modal__text {
        line-height: 1.6;
        max-height: 50vh;
        overflow: auto;
    }

    @media (max-width: 900px) {
        .ki-log-modal__grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 600px) {
        .ki-log-modal__panel {
            border-radius: 0;
            left: 0;
            max-height: 100vh;
            padding: 20px;
            top: 0;
            transform: none;
            width: 100vw;
        }

        .ki-log-modal h2 {
            font-size: 22px;
            margin-right: 34px;
        }

        .ki-log-modal__meta {
            display: block;
        }

        .ki-log-modal__meta span,
        .ki-log-modal__meta a {
            box-sizing: border-box;
            display: block;
            margin-bottom: 8px;
            width: 100%;
        }

        .ki-log-modal__text {
            max-height: none;
            overflow: visible;
        }
    }
    </style>';

    echo '<script>
    jQuery(document).ready(function($){
        function showKiLogDialog(options) {
            const settings = $.extend({
                title: "Hinweis",
                message: "",
                confirmText: "Weiter",
                cancelText: "Abbrechen",
                showCancel: false
            }, options || {});

            return new Promise(resolve => {
                const modal = $("<div />", {
                    class: "beitrag-dialog",
                    role: "dialog",
                    "aria-modal": "true"
                });
                const panel = $("<div />", {
                    class: "beitrag-dialog__panel"
                }).appendTo(modal);

                $("<button />", {
                    type: "button",
                    class: "beitrag-dialog__close",
                    text: "×",
                    "aria-label": "Hinweis schließen"
                }).appendTo(panel);

                $("<h2 />", {
                    text: settings.title
                }).appendTo(panel);

                $("<p />", {
                    class: "beitrag-dialog__message",
                    text: settings.message
                }).appendTo(panel);

                const actions = $("<div />", {
                    class: "beitrag-dialog__actions"
                }).appendTo(panel);

                if (settings.showCancel) {
                    $("<button />", {
                        type: "button",
                        class: "button button-secondary beitrag-dialog__cancel",
                        text: settings.cancelText
                    }).appendTo(actions);
                }

                $("<button />", {
                    type: "button",
                    class: "button button-primary beitrag-dialog__confirm",
                    text: settings.confirmText
                }).appendTo(actions);

                function close(result) {
                    modal.remove();
                    resolve(result);
                }

                modal.on("click", function(event){
                    if (event.target === modal[0]) {
                        close(false);
                    }
                });
                modal.find(".beitrag-dialog__close, .beitrag-dialog__cancel").on("click", function(){
                    close(false);
                });
                modal.find(".beitrag-dialog__confirm").on("click", function(){
                    close(true);
                });

                $("body").append(modal);
                modal.find(".beitrag-dialog__confirm").trigger("focus");
            });
        }

        $(".ki-log-open").on("click", function(){
            const target = $(this).data("target");
            $("#" + target).prop("hidden", false);
        });

        $("[data-close-modal]").on("click", function(){
            $(this).closest(".ki-log-modal").prop("hidden", true);
        });

        $(".ki-log-delete").on("click", async function(){
            const index = $(this).data("index");
            const confirmed = await showKiLogDialog({
                title: "KI-Protokolleintrag löschen?",
                message: "Der Eintrag wird dauerhaft aus dem lokalen KI-Protokoll entfernt.",
                confirmText: "Löschen",
                cancelText: "Abbrechen",
                showCancel: true
            });
            if (!confirmed) return;

            $.post(ajaxurl, {
                action: "beitragseinreichung_ki_log_loeschen",
                index: index,
                _wpnonce: "' . esc_js(wp_create_nonce('ki_log_loeschen')) . '"
            }, function(response){
                if (response.success) {
                    $("#ki-log-detail-" + index).remove();
                    $(this).closest("tr").remove();
                } else {
                    showKiLogDialog({
                        title: "Löschen fehlgeschlagen",
                        message: "Der Protokolleintrag konnte nicht gelöscht werden.",
                        confirmText: "Verstanden"
                    });
                }
            }.bind(this));
        });
    });
    </script>';
}
