<?php

declare(strict_types=1);

namespace Rector\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Rector\Config\Level\DeadCodeLevel;
use Rector\Configuration\Levels\LevelRulesResolver;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\DeadCode\Rector\FunctionLike\NarrowWideUnionReturnTypeRector;
use Rector\Exception\ShouldNotHappenException;

final class LevelRulesResolverTest extends TestCase
{
    public function testLevelZeroIsFirstRule(): void
    {
        $rules = LevelRulesResolver::resolve(0, DeadCodeLevel::RULES, __METHOD__);
        $this->assertSame([RemoveUnusedForeachKeyRector::class], $rules);
    }

    public function testLevelClamps(): void
    {
        $rules = LevelRulesResolver::resolve(1000, DeadCodeLevel::RULES, __METHOD__);
        $this->assertCount(59, $rules);
        $this->assertSame(NarrowWideUnionReturnTypeRector::class, $rules[58]);
    }

    public function testNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LevelRulesResolver::resolve(-1, DeadCodeLevel::RULES, __METHOD__);
    }

    public function testEmptyRulesThrow(): void
    {
        $this->expectException(ShouldNotHappenException::class);
        LevelRulesResolver::resolve(0, [], __METHOD__);
    }
}
