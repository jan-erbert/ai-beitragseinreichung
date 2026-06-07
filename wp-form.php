<?php

/**
 * Plugin Name: 🧠 AI Beitragseinreichung
 * Plugin URI: https://jan-erbert.de
 * Description: Ermöglicht es berechtigten Nutzern, im Backend Beiträge mit Bild und Schlagwörtern einzureichen. Beiträge werden mit Status "In Verarbeitung" gespeichert und können durch AI verbessert werden.
 * Version: 1.2.2
 * Author: Jan Erbert
 * License: GPL2+
 */

defined('ABSPATH') || exit;

define('BEITRAGSEINREICHUNG_VERSION', '1.2.2');

require_once plugin_dir_path(__FILE__) . 'includes/bootstrap.php';

// 0. Custom Capabilities registrieren (bei Plugin-Activation)
register_activation_hook(__FILE__, function () {
    beitragseinreichung_register_role_capabilities();
    // Verbindungstest beim Aktivieren durchführen
    beitragseinreichung_test_openai_verbindung();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $links[] = '<a href="' . admin_url('admin.php?page=beitragseinreichung_einstellungen') . '">⚙️ Einstellungen</a>';
    return $links;
});
