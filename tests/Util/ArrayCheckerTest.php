<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\Util\ArrayChecker;

final class ArrayCheckerTest extends TestCase
{
    private ArrayChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ArrayChecker();
    }

    public function testExists(): void
    {
        $this->assertTrue($this->checker->doesExist(['a', 'b', 'c'], static function ($e): bool {
            return $e === 'b';
        }));
    }

    public function testMissing(): void
    {
        $this->assertFalse($this->checker->doesExist(['a'], static function ($e): bool {
            return false;
        }));
    }
}
