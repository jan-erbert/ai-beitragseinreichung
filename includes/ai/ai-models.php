<?php

defined('ABSPATH') || exit;

/**
 * Liefert die zentrale KI-Modellkonfiguration.
 *
 * @return array{default_model: string, models: array<string, array<string, mixed>>}
 */
function beitrag_get_ai_model_config()
{
    return [
        'default_model' => 'gpt-5.6-terra',
        'models' => [
            'gpt-5.6-terra' => [
                'label' => 'GPT-5.6 Terra',
                'description' => 'Empfohlenes Standardmodell mit ausgewogenem Verhältnis aus Qualität und Kosten.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.6-luna' => [
                'label' => 'GPT-5.6 Luna',
                'description' => 'Sehr schnell und kostengünstig für einfache Beiträge und hohe Nutzungsmengen.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.6-sol' => [
                'label' => 'GPT-5.6 Sol',
                'description' => 'Hohe Qualität für anspruchsvolle und umfangreiche Textüberarbeitungen.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-6-astra' => [
                'label' => 'GPT-6 Astra',
                'description' => 'Neues Spitzenmodell für Ausnahmefälle; hohe Kosten und eingeschränkte Verfügbarkeit beachten.',
                'enabled' => false,
                'reasoning_effort' => 'low',
            ],
            'gpt-5.4-nano' => [
                'label' => 'GPT-5.4 nano',
                'description' => 'Sehr schnell und günstig für einfache Optimierungen.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.4-mini' => [
                'label' => 'GPT-5.4 mini',
                'description' => 'Bewährtes Modell für gute Qualität bei moderaten Kosten.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.4' => [
                'label' => 'GPT-5.4',
                'description' => 'Höhere Qualität für anspruchsvollere Textüberarbeitungen.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.5' => [
                'label' => 'GPT-5.5',
                'description' => 'Bewährtes Qualitätsmodell für anspruchsvolle Beiträge.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.5-pro' => [
                'label' => 'GPT-5.5 pro',
                'description' => 'Sehr leistungsstarke Pro-Variante für Ausnahmefälle; hohe Kosten und längere Laufzeit beachten.',
                'enabled' => false,
                'reasoning_effort' => 'high',
            ],
        ],
    ];
}

/**
 * Liefert alle konfigurierten KI-Modelle.
 *
 * @return array<string, array<string, mixed>>
 */
function beitrag_get_ai_models()
{
    $config = beitrag_get_ai_model_config();

    return $config['models'] ?? [];
}

/**
 * Liefert die im Plugin freigeschalteten KI-Modelle.
 *
 * @return array<string, array<string, mixed>>
 */
function beitrag_get_enabled_ai_models()
{
    return array_filter(beitrag_get_ai_models(), function (array $model) {
        return !empty($model['enabled']);
    });
}

/**
 * Liefert das Standardmodell.
 *
 * @return string
 */
function beitrag_get_default_ai_model()
{
    $config = beitrag_get_ai_model_config();
    $default_model = trim((string) ($config['default_model'] ?? ''));
    $enabled_models = beitrag_get_enabled_ai_models();

    if ($default_model !== '' && isset($enabled_models[$default_model])) {
        return $default_model;
    }

    foreach ($enabled_models as $model_id => $model_config) {
        return $model_id;
    }

    return '';
}

/**
 * Liefert das Fallbackmodell.
 *
 * @return string
 */
function beitrag_get_fallback_ai_model()
{
    return beitrag_get_default_ai_model();
}

/**
 * Normalisiert gespeicherte oder uebergebene Modellwerte.
 *
 * @param mixed $model Gespeicherter oder uebergebener Modellwert.
 * @return string
 */
function beitrag_normalize_ai_model($model)
{
    $model = trim((string) $model);
    $enabled_models = beitrag_get_enabled_ai_models();

    if ($model !== '' && isset($enabled_models[$model])) {
        return $model;
    }

    return beitrag_get_fallback_ai_model();
}

/**
 * Prueft, ob ein Modell aktuell freigeschaltet ist.
 *
 * @param mixed $model Gespeicherter oder uebergebener Modellwert.
 * @return bool
 */
function beitrag_is_known_ai_model($model)
{
    $model = trim((string) $model);
    $enabled_models = beitrag_get_enabled_ai_models();

    return $model !== '' && isset($enabled_models[$model]);
}

/**
 * Liefert den Anzeigenamen fuer einen Modellwert.
 *
 * @param mixed $model Gespeicherter oder uebergebener Modellwert.
 * @return string
 */
function beitrag_get_ai_model_display_name($model)
{
    $model = beitrag_normalize_ai_model($model);
    $models = beitrag_get_ai_models();

    if (isset($models[$model]['label'])) {
        return $models[$model]['label'] . ' (' . $model . ')';
    }

    return $model;
}

/**
 * Liefert optionale Request-Parameter fuer ein Modell.
 *
 * @param mixed $model Gespeicherter oder uebergebener Modellwert.
 * @return array<string, array<string, string>>
 */
function beitrag_get_ai_model_request_options($model)
{
    $model = beitrag_normalize_ai_model($model);
    $models = beitrag_get_ai_models();
    $model_config = $models[$model] ?? [];
    $options = [];

    if (!empty($model_config['reasoning_effort'])) {
        $options['reasoning'] = [
            'effort' => $model_config['reasoning_effort'],
        ];
    }

    return $options;
}
