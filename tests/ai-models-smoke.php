<?php

define('ABSPATH', dirname(__DIR__));

require __DIR__ . '/test-helpers.php';
require dirname(__DIR__) . '/includes/ai/ai-models.php';

$enabled_models = array_keys(beitrag_get_enabled_ai_models());

beitrag_test_assert_same('gpt-5.6-terra', beitrag_get_default_ai_model(), 'Terra ist nicht das Standardmodell.');
beitrag_test_assert_same('gpt-5.6-terra', beitrag_normalize_ai_model('gpt-5.2'), 'Ein veraltetes Modell faellt nicht auf Terra zurueck.');
beitrag_test_assert(in_array('gpt-5.6-luna', $enabled_models, true), 'Luna ist nicht freigeschaltet.');
beitrag_test_assert(in_array('gpt-5.6-sol', $enabled_models, true), 'Sol ist nicht freigeschaltet.');
beitrag_test_assert(!in_array('gpt-6-astra', $enabled_models, true), 'Astra darf noch nicht freigeschaltet sein.');
beitrag_test_assert_same(
    ['reasoning' => ['effort' => 'none']],
    beitrag_get_ai_model_request_options('gpt-5.6-terra'),
    'Terra liefert unerwartete Request-Optionen.'
);

echo "KI-Modell-Smoke-Test erfolgreich.\n";
