<?php

defined('ABSPATH') || exit;

// 2. Nur Admins duerfen im Protokoll loeschen
add_action('wp_ajax_beitragseinreichung_ki_log_loeschen', function () {
    if (!current_user_can('beitragseinreichung_admin')) {
        wp_send_json_error('Keine Berechtigung');
    }
    check_ajax_referer('ki_log_loeschen');

    $index = isset($_POST['index']) ? (int) $_POST['index'] : -1;
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

    if (empty($logs)) {
        echo '<p>Keine KI-Optimierungen wurden bisher protokolliert.</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>
        <th>Datum</th>
        <th>Beitrag</th>
        <th>Autor</th>
        <th>Details</th>
    </tr></thead>';
    echo '<tbody>';

    foreach (array_reverse($logs) as $index => $log) {
        $autor = get_userdata($log['autor_id']);
        $post_link = get_edit_post_link($log['post_id']);
        $autor_name = $autor ? esc_html($autor->display_name) : 'Unbekannt';

        echo '<tr>';
        echo '<td>' . esc_html($log['zeit']) . '</td>';
        echo '<td><a href="' . esc_url($post_link) . '">' . esc_html($log['titel']) . '</a></td>';
        echo '<td>' . $autor_name . '</td>';
        echo '<td>';
        echo '<button class="button ki-log-toggle" data-index="' . esc_attr($index) . '">Anzeigen</button>';
        if (current_user_can('beitragseinreichung_admin')) {
            echo ' <button class="button ki-log-delete" data-index="' . esc_attr($index) . '">Löschen</button>';
        }
        echo '</td>';

        // Versteckte Detail-Zeile
        echo '<tr class="ki-log-detail" id="ki-log-' . esc_attr($index) . '" style="display:none;">';
        echo '<td colspan="4">';
        echo '<strong>Originaltitel:</strong><br>' . nl2br(esc_html($log['original_titel'])) . '<br><br>';
        echo '<strong>Optimierter Titel:</strong><br>' . nl2br(esc_html($log['optimierter_titel'])) . '<hr>';
        echo '<strong>Originaltext:</strong><br>' . nl2br(esc_html($log['original_inhalt'])) . '<br><br>';
        echo '<strong>Optimierter Text:</strong><br>' . nl2br(esc_html($log['optimierter_inhalt']));
        if (!empty($log['excerpt'])) {
            echo '<hr><strong>Textauszug:</strong><br>' . nl2br(esc_html($log['excerpt'])) . '<br><br>';
        }
        echo '<br><br><strong>Verwendetes Modell:</strong> ' . esc_html($log['modell'] ?? 'unbekannt');
        $stilgruppe = isset($log['stilgruppe']) && trim($log['stilgruppe']) !== '' ? $log['stilgruppe'] : '<unbekannt>';
        echo '<br><strong>Stilgruppe:</strong> ' . esc_html($stilgruppe);

        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // JS zum Ein-/Ausklappen
    echo '<script>
    jQuery(document).ready(function($){
        $(".ki-log-toggle").on("click", function(){
            const index = $(this).data("index");
            $("#ki-log-" + index).toggle();
        });

        $(".ki-log-delete").on("click", function(){
            const index = $(this).data("index");
            if (!confirm("Willst du diesen Eintrag wirklich löschen?")) return;

            $.post(ajaxurl, {
                action: "beitragseinreichung_ki_log_loeschen",
                index: index,
                _wpnonce: "' . wp_create_nonce('ki_log_loeschen') . '"
            }, function(response){
                if (response.success) {
                    $("#ki-log-" + index).prev().remove(); // Tabellenzeile
                    $("#ki-log-" + index).remove(); // Detail-Zeile
                } else {
                    alert("Fehler beim Löschen.");
                }
            });
        });
    });
    </script>';
}
