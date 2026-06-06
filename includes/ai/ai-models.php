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
        'default_model' => 'gpt-5.4-mini',
        'models' => [
            'gpt-5.4-nano' => [
                'label' => 'GPT-5.4 nano',
                'description' => 'Sehr schnell und guenstig fuer einfache Optimierungen.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.4-mini' => [
                'label' => 'GPT-5.4 mini',
                'description' => 'Empfohlenes Standardmodell fuer gute Qualitaet bei moderaten Kosten.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.4' => [
                'label' => 'GPT-5.4',
                'description' => 'Hoehere Qualitaet fuer anspruchsvollere Textueberarbeitungen.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.5' => [
                'label' => 'GPT-5.5',
                'description' => 'Staerkstes Modell fuer sehr anspruchsvolle Beitraege; Kosten vor dauerhafter Nutzung beachten.',
                'enabled' => true,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.5-pro' => [
                'label' => 'GPT-5.5 pro',
                'description' => 'Sehr leistungsstarke Pro-Variante fuer Ausnahmefaelle; hohe Kosten und laengere Laufzeit beachten.',
                'enabled' => false,
                'reasoning_effort' => 'high',
            ],
            'gpt-5.2' => [
                'label' => 'GPT-5.2',
                'description' => 'Vorheriges Frontier-Modell als stabile Reserve fuer Vergleichstests.',
                'enabled' => false,
                'reasoning_effort' => 'none',
            ],
            'gpt-5.2-pro' => [
                'label' => 'GPT-5.2 pro',
                'description' => 'Pro-Reserve fuer sehr anspruchsvolle Textpruefungen; nur bewusst freischalten.',
                'enabled' => false,
                'reasoning_effort' => 'high',
            ],
            'gpt-5' => [
                'label' => 'GPT-5',
                'description' => 'Aelteres GPT-5-Modell als Kompatibilitaets- und Vergleichsreserve.',
                'enabled' => false,
                'reasoning_effort' => 'none',
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
