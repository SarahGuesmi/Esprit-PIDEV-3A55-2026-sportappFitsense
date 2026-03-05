<?php

use App\Kernel;

// Suppress deprecation warnings from old SendGrid library
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Standardize timezone to UTC to match MySQL +00:00 offset
date_default_timezone_set('UTC');

// Polyfill for mbstring if missing
if (!function_exists('mb_strlen')) {
    function mb_strlen(?string $string, ?string $encoding = null): int {
        return strlen((string)$string);
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
