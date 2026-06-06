<?php

defined('ABSPATH') || exit;

/**
 * Testet die OpenAI-Verbindung per Admin-AJAX.
 */
add_action('wp_ajax_beitragseinreichung_test_openai_jetzt', function () {
    if (!current_user_can('beitragseinreichung_settings')) {
        wp_send_json_error('Keine Berechtigung');
    }

    check_ajax_referer('test_openai_ajax');

    $status = beitragseinreichung_test_openai_verbindung();

    if ($status['status'] === 'erfolgreich') {
        wp_send_json_success($status['info']);
    }

    wp_send_json_error($status['info']);
});
