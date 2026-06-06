<?php

defined('ABSPATH') || exit;

/**
 * Liest JSON aus einer KI-Antwort robust aus.
 */
function beitrag_ki_parse_json_antwort($content)
{
    $content = trim((string) $content);
    $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    $data = json_decode($content, true);
    if (is_array($data)) {
        return $data;
    }

    if (preg_match('/\{.*\}/s', $content, $matches)) {
        $data = json_decode($matches[0], true);
        if (is_array($data)) {
            return $data;
        }
    }

    return null;
}
