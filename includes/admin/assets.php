<?php

defined('ABSPATH') || exit;

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_beitragseinreichung') return;
    wp_enqueue_media(); // laedt den Media Uploader

    wp_enqueue_script(
        'beitragseinreichung-lottie-player',
        'https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js',
        [],
        null,
        true
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_beitragseinreichung') return;

    wp_enqueue_style(
        'beitragseinreichung-style',
        plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php') . 'css/style.css',
        [],
        '1.0'
    );
});
