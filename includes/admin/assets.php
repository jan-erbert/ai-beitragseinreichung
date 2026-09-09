<?php

defined('ABSPATH') || exit;

/**
 * Prueft, ob eine Plugin-Adminseite angezeigt wird.
 */
function beitragseinreichung_is_admin_asset_page()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check for conditional admin assets.
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    return in_array(
        $page,
        [
            'beitragseinreichung',
            'beitragseinreichung_einstellungen',
            'beitragseinreichung_ki_protokoll',
        ],
        true
    );
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_beitragseinreichung') {
        wp_enqueue_media(); // laedt den Media Uploader
    }

    if (!beitragseinreichung_is_admin_asset_page()) {
        return;
    }

    $lottie_path = dirname(__DIR__, 2) . '/assets/js/lottie-player.js';
    $lottie_version = '2.0.1';
    if (file_exists($lottie_path)) {
        $lottie_version .= '-' . filemtime($lottie_path);
    }

    wp_enqueue_script(
        'beitragseinreichung-lottie-player',
        plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php') . 'assets/js/lottie-player.js',
        [],
        $lottie_version,
        true
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (!beitragseinreichung_is_admin_asset_page()) {
        return;
    }

    $style_path = plugin_dir_path(dirname(__DIR__)) . 'css/style.css';
    $style_version = defined('BEITRAGSEINREICHUNG_VERSION') ? BEITRAGSEINREICHUNG_VERSION : '1.2.6';
    if (file_exists($style_path)) {
        $style_version .= '-' . filemtime($style_path);
    }

    wp_enqueue_style(
        'beitragseinreichung-style',
        plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php') . 'css/style.css',
        [],
        $style_version
    );
});
