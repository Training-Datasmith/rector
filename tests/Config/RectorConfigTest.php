<?php

declare(strict_types=1);

namespace Rector\Tests\Config;

use PHPUnit\Framework\TestCase;
use Rector\Config\RectorConfig;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\Enum\Config\Defaults;
use Rector\Exception\ShouldNotHappenException;
use Rector\Tests\Support\StaticStateReset;
use Rector\ValueObject\PhpVersion;

final class RectorConfigTest extends TestCase
{
    private RectorConfig $config;

    protected function setUp(): void
    {
        StaticStateReset::reset();
        $this->config = new RectorConfig();
    }

    protected function tearDown(): void
    {
        StaticStateReset::reset();
    }

    public function testPathsExistingAndGlob(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'rector-path');
        $this->config->paths([$file, 'src/*.php']);
        $paths = SimpleParameterProvider::provideArrayParameter(Option::PATHS);
        $this->assertContains($file, $paths);
        $this->assertContains('src/*.php', $paths);
        @unlink($file);
    }

    public function testPathsMissingThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->config->paths([sys_get_temp_dir() . '/rector-missing-' . uniqid('', true)]);
    }

    public function testImportRunsCallable(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'rector-cfg');
        $code = '<?php return static function (Rector\\Config\\RectorConfig $c): void { $c->phpVersion(' . PhpVersion::PHP_80 . '); };';
        file_put_contents($file, $code);
        $this->config->import($file);
        $this->assertSame(PhpVersion::PHP_80, SimpleParameterProvider::provideIntParameter(Option::PHP_VERSION_FEATURES));
        @unlink($file);
    }

    public function testImportGlobThrows(): void
    {
        $this->expectException(ShouldNotHappenException::class);
        $this->config->import('does-not-exist-*.php');
    }

    public function testRuleRegistrationAndDuplicate(): void
    {
        $this->config->rule(RemoveAndTrueRector::class);
        $classes = $this->config->getMainRectorClasses();
        $this->assertContains(RemoveAndTrueRector::class, $classes);
        $this->expectException(ShouldNotHappenException::class);
        $this->config->rules([RemoveAndTrueRector::class, RemoveAndTrueRector::class]);
    }

    public function testParallelToggle(): void
    {
        $this->config->parallel(15, 3, 4);
        $this->assertTrue(SimpleParameterProvider::provideBoolParameter(Option::PARALLEL));
        $this->assertSame(15, SimpleParameterProvider::provideIntParameter(Option::PARALLEL_JOB_TIMEOUT_IN_SECONDS));
        $this->assertSame(3, SimpleParameterProvider::provideIntParameter(Option::PARALLEL_MAX_NUMBER_OF_PROCESSES));
        $this->assertSame(4, SimpleParameterProvider::provideIntParameter(Option::PARALLEL_JOB_SIZE));
        $this->config->disableParallel();
        $this->assertFalse(SimpleParameterProvider::provideBoolParameter(Option::PARALLEL));
    }

    public function testSkipUnknownRuleThrows(): void
    {
        $this->expectException(ShouldNotHappenException::class);
        $this->config->skip(['Rector\\Tests\\Config\\ThisSkipTargetRectorDoesNotExistRector']);
    }

    public function testSkipExistingRule(): void
    {
        $this->config->skip([RemoveAndTrueRector::class]);
        $skipped = SimpleParameterProvider::provideArrayParameter(Option::SKIPPED_RECTOR_RULES);
        $this->assertContains(RemoveAndTrueRector::class, $skipped);
    }

    public function testConfigureTwiceRecreatedFlag(): void
    {
        StaticStateReset::reset();
        RectorConfig::configure();
        $first = SimpleParameterProvider::provideBoolParameter(Option::IS_RECTORCONFIG_BUILDER_RECREATED);
        $this->assertFalse($first);
        RectorConfig::configure();
        $second = SimpleParameterProvider::provideBoolParameter(Option::IS_RECTORCONFIG_BUILDER_RECREATED);
        $this->assertTrue($second);
    }
}
