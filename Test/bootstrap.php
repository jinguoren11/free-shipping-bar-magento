<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 * Minimal bootstrap so unit tests run without a Magento installation.
 */
declare(strict_types=1);

$autoload = getenv('FSB_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
require_once $autoload;

// Load the module's own classes without needing Composer to link the package.
spl_autoload_register(static function (string $class): void {
    $prefix = 'GetDropFlow\\FreeShippingBar\\';
    if (strpos($class, $prefix) === 0) {
        $file = dirname(__DIR__) . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

if (!function_exists('__')) {
    /**
     * Stand-in for Magento's translation function: fills %1, %2 ... like the real one.
     *
     * @param string $text
     * @param mixed ...$args
     * @return string
     */
    function __($text, ...$args)
    {
        foreach ($args as $i => $arg) {
            $text = str_replace('%' . ($i + 1), (string) $arg, $text);
        }
        return $text;
    }
}
