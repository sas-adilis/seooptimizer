<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * JSON answer of the AJAX actions. Anything printed before it (PHP notice, deprecation...)
 * would break the JSON parsing in the browser: it is discarded and logged instead.
 */
class JsonResponse
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function send(array $payload)
    {
        $stray = '';
        while (ob_get_level() > 0) {
            $stray .= (string) ob_get_clean();
        }
        if (trim($stray) !== '') {
            \PrestaShopLogger::addLog('SeoOptimizer: unexpected output before the JSON answer: ' . mb_substr(strip_tags($stray), 0, 500), 3, null, 'SeoOptimizer');
        }

        $json = json_encode($payload, JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \PrestaShopLogger::addLog('SeoOptimizer: JSON encoding error: ' . json_last_error_msg(), 3, null, 'SeoOptimizer');
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo $json;
        exit;
    }
}
