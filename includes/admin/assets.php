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

    wp_enqueue_script(
        'beitragseinreichung-lottie-player',
        'https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js',
        [],
        null,
        true
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (!beitragseinreichung_is_admin_asset_page()) {
        return;
    }

    $style_path = plugin_dir_path(dirname(__DIR__)) . 'css/style.css';
    $style_version = defined('BEITRAGSEINREICHUNG_VERSION') ? BEITRAGSEINREICHUNG_VERSION : '1.2.4';
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
