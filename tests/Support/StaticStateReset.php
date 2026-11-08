<?php

declare(strict_types=1);

namespace Rector\Tests\Support;

use ReflectionClass;
use Rector\Config\RectorConfig;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\Php\PhpVersionResolver\ComposerJsonPhpVersionResolver;
use RectorPrefix202603\Illuminate\Container\Container;

final class StaticStateReset
{
    public static function reset(): void
    {
        $provider = new ReflectionClass(SimpleParameterProvider::class);
        $params = $provider->getProperty('parameters');
        if (\PHP_VERSION_ID < 80100) {
            $params->setAccessible(true);
        }
        $params->setValue(null, []);

        $config = new ReflectionClass(RectorConfig::class);
        $recreated = $config->getProperty('recreated');
        if (\PHP_VERSION_ID < 80100) {
            $recreated->setAccessible(true);
        }
        $recreated->setValue(null, null);

        $resolver = new ReflectionClass(ComposerJsonPhpVersionResolver::class);
        $cache = $resolver->getProperty('cachedPhpVersions');
        if (\PHP_VERSION_ID < 80100) {
            $cache->setAccessible(true);
        }
        $cache->setValue(null, []);

        Container::setInstance(null);
    }
}
