<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\Util\ArrayParametersMerger;

final class ArrayParametersMergerTest extends TestCase
{
    private ArrayParametersMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new ArrayParametersMerger();
    }

    public function testLeftScalarWins(): void
    {
        $this->assertSame('a', $this->merger->merge('a', 'b'));
    }

    public function testNullLeftUsesRightArray(): void
    {
        $this->assertSame(['x'], $this->merger->merge(null, ['x']));
    }

    public function testNestedStringKeys(): void
    {
        $left = ['a' => 1, 'b' => ['c' => 2]];
        $right = ['a' => 9, 'b' => ['d' => 3], 'e' => 4];
        $this->assertSame(['a' => 1, 'b' => ['d' => 3, 'c' => 2], 'e' => 4], $this->merger->merge($left, $right));
    }

    public function testNumericKeysAppendUnique(): void
    {
        $this->assertSame([2, 3, 1], $this->merger->merge([1, 2], [2, 3]));
    }
}
