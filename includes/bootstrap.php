<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'core/capabilities.php';
require_once plugin_dir_path(__FILE__) . 'ai/ai-models.php';
require_once plugin_dir_path(__FILE__) . 'ai/ai-notifications.php';
require_once plugin_dir_path(__FILE__) . 'core/post-status.php';
require_once plugin_dir_path(__FILE__) . 'core/post-builder.php';
require_once plugin_dir_path(__FILE__) . 'core/post-handler.php';
require_once plugin_dir_path(__FILE__) . 'ai/prompt-builder.php';
require_once plugin_dir_path(__FILE__) . 'ai/response-parser.php';
require_once plugin_dir_path(__FILE__) . 'ai/openai-client.php';
require_once plugin_dir_path(__FILE__) . 'ai/ai-logging.php';
require_once plugin_dir_path(__FILE__) . 'formatting/gutenberg-formatting.php';
require_once plugin_dir_path(__FILE__) . 'admin/menu.php';
require_once plugin_dir_path(__FILE__) . 'admin/assets.php';
require_once plugin_dir_path(__FILE__) . 'admin/form-footer-scripts.php';
require_once plugin_dir_path(__FILE__) . 'admin/openai-ajax.php';
require_once plugin_dir_path(__FILE__) . 'admin/update-popup.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'frontend/gallery-slider.php';
require_once plugin_dir_path(__FILE__) . 'frontend/submission-form.php';
