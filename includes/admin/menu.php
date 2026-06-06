<?php

defined('ABSPATH') || exit;

/**
 * Registriert die Admin-Menuepunkte des Plugins.
 */
add_action('admin_menu', function () {
    if (current_user_can('beitragseinreichung_submit') || current_user_can('beitragseinreichung_settings')) {
        add_menu_page(
            'Beitrag einreichen',
            'Beitrag einreichen',
            'read',
            'beitragseinreichung',
            function () {
                if (!current_user_can('beitragseinreichung_submit')) {
                    wp_die(esc_html__('Du hast keine Berechtigung, diese Seite zu sehen.'));
                }
                beitragseinreichung_formular_anzeige();
            },
            'dashicons-edit',
            20
        );
    }

    if (current_user_can('beitragseinreichung_settings')) {
        add_submenu_page(
            'beitragseinreichung',
            'Beitragseinreichung – Einstellungen',
            'Einstellungen',
            'beitragseinreichung_settings',
            'beitragseinreichung_einstellungen',
            'beitragseinreichung_einstellungen_anzeige'
        );
    }

    if (current_user_can('beitragseinreichung_submit')) {
        add_submenu_page(
            'beitragseinreichung',
            'KI-Protokoll',
            'KI-Protokoll',
            'beitragseinreichung_submit',
            'beitragseinreichung_ki_protokoll',
            'beitragseinreichung_ki_log_anzeige'
        );
    }
});
