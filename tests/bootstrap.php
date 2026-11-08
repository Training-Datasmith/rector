<?php

declare(strict_types=1);

$repoRoot = dirname(__DIR__);

require $repoRoot . '/vendor/autoload.php';

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $repoRoot . '/vendor/autoload.php');
}

spl_autoload_register(static function (string $class) use ($repoRoot): void {
    if (strpos($class, 'Rector\\Tests\\') !== 0) {
        return;
    }
    $relative = substr($class, strlen('Rector\\Tests\\'));
    $path = $repoRoot . '/tests/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
