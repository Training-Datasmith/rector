<?php

declare(strict_types=1);

namespace Rector\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\Exception\ShouldNotHappenException;
use Rector\Tests\Support\StaticStateReset;

final class SimpleParameterProviderTest extends TestCase
{
    protected function setUp(): void
    {
        StaticStateReset::reset();
    }

    protected function tearDown(): void
    {
        StaticStateReset::reset();
    }

    public function testSetProvideString(): void
    {
        SimpleParameterProvider::setParameter(Option::PATHS, 'src');
        $this->assertSame('src', SimpleParameterProvider::provideStringParameter(Option::PATHS));
    }

    public function testMissingStringThrows(): void
    {
        $this->expectException(ShouldNotHappenException::class);
        SimpleParameterProvider::provideStringParameter(Option::PATHS);
    }

    public function testDefaultString(): void
    {
        $this->assertSame('fallback', SimpleParameterProvider::provideStringParameter(Option::PATHS, 'fallback'));
    }

    public function testBool(): void
    {
        SimpleParameterProvider::setParameter(Option::PARALLEL, true);
        $this->assertTrue(SimpleParameterProvider::provideBoolParameter(Option::PARALLEL));
        $this->assertFalse(SimpleParameterProvider::provideBoolParameter(Option::NO_DIFFS, false));
    }

    public function testAddListDedupes(): void
    {
        SimpleParameterProvider::addParameter(Option::REGISTERED_RECTOR_RULES, 'Rule');
        SimpleParameterProvider::addParameter(Option::REGISTERED_RECTOR_RULES, 'Rule');
        $list = SimpleParameterProvider::provideArrayParameter(Option::REGISTERED_RECTOR_RULES);
        $this->assertSame(['Rule'], $list);
        $this->assertSame([0], array_keys($list));
    }

    public function testAssociativeNotDeduped(): void
    {
        SimpleParameterProvider::setParameter('map', ['a' => 1, 'b' => 1]);
        $this->assertSame(['a' => 1, 'b' => 1], SimpleParameterProvider::provideArrayParameter('map'));
    }

    public function testHashChanges(): void
    {
        SimpleParameterProvider::setParameter('k', 'v1');
        $h1 = SimpleParameterProvider::hash();
        SimpleParameterProvider::setParameter('k', 'v2');
        $this->assertNotSame($h1, SimpleParameterProvider::hash());
    }

    public function testHasParameter(): void
    {
        $this->assertFalse(SimpleParameterProvider::hasParameter(Option::PATHS));
        SimpleParameterProvider::setParameter(Option::PATHS, 'x');
        $this->assertTrue(SimpleParameterProvider::hasParameter(Option::PATHS));
    }
}
