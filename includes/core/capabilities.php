<?php

defined('ABSPATH') || exit;

/**
 * Registriert die Plugin-Capabilities fuer Standardrollen.
 */
function beitragseinreichung_register_role_capabilities()
{
    $roles = ['administrator', 'editor', 'author'];
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if (!$role) continue;

        // Basisrechte zuweisen
        $role->add_cap('beitragseinreichung_submit');
        if ($role_name === 'administrator') {
            $role->add_cap('beitragseinreichung_settings');
            $role->add_cap('beitragseinreichung_admin');
        }
    }
}

add_action('members_register_cap_groups', 'register_ai_beitragseinreichung_cap_group');
/**
 * Registriert die Capability-Gruppe fuer das Members-Plugin.
 */
function register_ai_beitragseinreichung_cap_group()
{
    members_register_cap_group('ai_beitragseinreichung', [
        'label' => __('AI Beitragseinreichung', 'ai-beitragseinreichung'),
        'icon' => 'dashicons-edit',
        'priority' => 10,
    ]);
}

add_action('members_register_caps', 'register_ai_beitragseinreichung_caps');
/**
 * Registriert die Plugin-Capabilities fuer das Members-Plugin.
 */
function register_ai_beitragseinreichung_caps()
{
    members_register_cap('beitragseinreichung_submit', [
        'label' => __('Beiträge einreichen', 'ai-beitragseinreichung'),
        'group' => 'ai_beitragseinreichung',
    ]);
    members_register_cap('beitragseinreichung_settings', [
        'label' => __('Einstellungen verwalten', 'ai-beitragseinreichung'),
        'group' => 'ai_beitragseinreichung',
    ]);
    members_register_cap('beitragseinreichung_admin', [
        'label' => __('Erweiterte Admin-Rechte (API-Key etc.)', 'ai-beitragseinreichung'),
        'group' => 'ai_beitragseinreichung',
    ]);
}
