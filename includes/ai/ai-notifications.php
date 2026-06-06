<?php

defined('ABSPATH') || exit;

/**
 * Benachrichtigt den Admin ueber Fehler bei der KI-Optimierung.
 */
function beitrag_ki_admin_benachrichtigen($fehlermeldung)
{
    $admin_email = get_option('admin_email');
    $benutzer = wp_get_current_user();
    $zeit = current_time('mysql');

    $betreff = '❌ Fehler bei der KI-Optimierung im Plugin';
    $nachricht = "Es ist ein Fehler bei der KI-Optimierung aufgetreten.\n\n";
    $nachricht .= "Fehler: $fehlermeldung\n";
    $nachricht .= "Benutzer: {$benutzer->user_login}\n";
    $nachricht .= "Zeitpunkt: $zeit\n\n";
    $nachricht .= "Bitte pruefe die Server-Logs oder die API-Verbindung.";

    wp_mail($admin_email, $betreff, $nachricht);
}
