<?php

declare(strict_types=1);

namespace Rector\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Rector\Config\Level\DeadCodeLevel;
use Rector\Config\RectorConfig;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\Configuration\RectorConfigBuilder;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\Tests\Support\StaticStateReset;
use Rector\ValueObject\PhpVersion;

final class RectorConfigBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        StaticStateReset::reset();
    }

    protected function tearDown(): void
    {
        StaticStateReset::reset();
    }

    public function testInvokeAppliesPathsPhpVersionAndRule(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'rector-b');
        $config = new RectorConfig();
        $builder = (new RectorConfigBuilder())
            ->withPaths([$file])
            ->withPhpVersion(PhpVersion::PHP_81)
            ->withRules([RemoveAndTrueRector::class]);
        $builder($config);
        $this->assertSame(PhpVersion::PHP_81, SimpleParameterProvider::provideIntParameter(Option::PHP_VERSION_FEATURES));
        $paths = SimpleParameterProvider::provideArrayParameter(Option::PATHS);
        $this->assertContains($file, $paths);
        $this->assertContains(RemoveAndTrueRector::class, $config->getMainRectorClasses());
        @unlink($file);
    }

    public function testDeadCodeLevelZeroRegistersOnlyFirstRule(): void
    {
        $config = new RectorConfig();
        $builder = (new RectorConfigBuilder())->withDeadCodeLevel(0);
        $builder($config);
        $this->assertSame([RemoveUnusedForeachKeyRector::class], $config->getMainRectorClasses());
    }

    public function testDeadCodeLevelOverflowRecorded(): void
    {
        $config = new RectorConfig();
        $builder = (new RectorConfigBuilder())->withDeadCodeLevel(1000);
        $builder($config);
        $overflows = SimpleParameterProvider::provideArrayParameter(Option::LEVEL_OVERFLOWS);
        $this->assertCount(1, $overflows);
        $overflow = $overflows[0];
        $this->assertSame('withDeadCodeLevel', $overflow->getConfigurationName());
        $this->assertSame(1000, $overflow->getLevel());
        $this->assertSame(count(DeadCodeLevel::RULES), $overflow->getRuleCount());
        $this->assertSame('deadCode', $overflow->getSuggestedRuleset());
        $this->assertSame('DEAD_CODE', $overflow->getSuggestedSetListConstant());
    }
}
