<?php

defined('ABSPATH') || exit;

/**
 * Liefert die zentral gepflegten KI-Modellprofile.
 */
function beitrag_get_ai_model_profiles()
{
    return [
        'standard' => [
            'label' => 'Standard – schnell und günstig',
            'model' => 'gpt-4o-mini',
            'description' => 'Für normale Beitragseinreichungen und Textauszüge empfohlen.',
        ],
        'quality' => [
            'label' => 'Qualität – bessere Textüberarbeitung',
            'model' => 'gpt-4.1-mini',
            'description' => 'Für anspruchsvollere sprachliche Überarbeitungen.',
        ],
        'premium' => [
            'label' => 'Premium – höchste Qualität',
            'model' => 'gpt-4.1',
            'description' => 'Für besonders anspruchsvolle Beiträge, wenn Kosten weniger wichtig sind.',
        ],
    ];
}

/**
 * Liefert das Standardmodell.
 */
function beitrag_get_default_ai_model()
{
    return 'gpt-4o-mini';
}

/**
 * Liefert das Fallbackmodell.
 */
function beitrag_get_fallback_ai_model()
{
    return 'gpt-4o-mini';
}

/**
 * Liefert die Zuordnung alter Modellwerte.
 */
function beitrag_get_legacy_ai_model_map()
{
    return [
        'gpt-3.5-turbo' => 'gpt-4o-mini',
        'gpt-4' => 'gpt-4.1',
        'gpt-4-turbo' => 'gpt-4.1-mini',
    ];
}

/**
 * Normalisiert gespeicherte oder übergebene Modellwerte.
 */
function beitrag_normalize_ai_model($model)
{
    $model = trim((string) $model);

    if ($model === '') {
        return beitrag_get_fallback_ai_model();
    }

    $legacy_map = beitrag_get_legacy_ai_model_map();
    if (isset($legacy_map[$model])) {
        return $legacy_map[$model];
    }

    if (beitrag_is_known_ai_model($model)) {
        return $model;
    }

    return beitrag_get_fallback_ai_model();
}

/**
 * Prüft, ob ein Modellprofil oder Legacy-Wert bekannt ist.
 */
function beitrag_is_known_ai_model($model)
{
    $model = trim((string) $model);

    if ($model === '') {
        return false;
    }

    foreach (beitrag_get_ai_model_profiles() as $profile) {
        if (($profile['model'] ?? '') === $model) {
            return true;
        }
    }

    return isset(beitrag_get_legacy_ai_model_map()[$model]);
}
