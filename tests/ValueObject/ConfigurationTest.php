<?php

declare(strict_types=1);

namespace Rector\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\DeadCode\Rector\If_\RemoveDeadIfBlockRector;
use Rector\Tests\Support\StaticStateReset;
use Rector\ValueObject\Configuration;

final class ConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        StaticStateReset::reset();
    }

    protected function tearDown(): void
    {
        StaticStateReset::reset();
    }

    public function testConstructorAccessors(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rector-cfg');
        $configuration = new Configuration(
            true,
            false,
            true,
            'json',
            ['php', 'inc'],
            [$path],
            false,
            null,
            null,
            true,
            '128M',
            true,
            false,
            RemoveAndTrueRector::class
        );
        $this->assertTrue($configuration->isDryRun());
        $this->assertFalse($configuration->shouldShowProgressBar());
        $this->assertSame(['php', 'inc'], $configuration->getFileExtensions());
        $this->assertSame([$path], $configuration->getPaths());
        $this->assertSame('json', $configuration->getOutputFormat());
        $this->assertSame('128M', $configuration->getMemoryLimit());
        $this->assertSame(RemoveAndTrueRector::class, $configuration->getOnlyRule());
        $this->assertTrue($configuration->isDebug());
        @unlink($path);
    }

    public function testDuplicatedRuleRegistration(): void
    {
        SimpleParameterProvider::addParameter(Option::ROOT_STANDALONE_REGISTERED_RULES, RemoveAndTrueRector::class);
        SimpleParameterProvider::addParameter(Option::ROOT_STANDALONE_REGISTERED_RULES, RemoveDeadIfBlockRector::class);
        SimpleParameterProvider::addParameter(Option::SET_REGISTERED_RULES, RemoveAndTrueRector::class);
        $configuration = new Configuration();
        $this->assertSame([RemoveAndTrueRector::class], $configuration->getBothSetAndRulesDuplicatedRegistrations());
    }
}
