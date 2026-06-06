<?php

defined('ABSPATH') || exit;

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_beitragseinreichung') return;
    wp_enqueue_media(); // laedt den Media Uploader
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
