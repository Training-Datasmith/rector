<?php

declare(strict_types=1);

namespace Rector\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Rector\ValueObject\PhpVersion;

final class PhpVersionTest extends TestCase
{
    public function testMonotonicAnd82(): void
    {
        $this->assertLessThan(PhpVersion::PHP_80, PhpVersion::PHP_74);
        $this->assertLessThan(PhpVersion::PHP_81, PhpVersion::PHP_80);
        $this->assertLessThan(PhpVersion::PHP_82, PhpVersion::PHP_81);
        $this->assertLessThan(PhpVersion::PHP_83, PhpVersion::PHP_82);
        $this->assertLessThan(PhpVersion::PHP_84, PhpVersion::PHP_83);
        $this->assertLessThan(PhpVersion::PHP_85, PhpVersion::PHP_84);
        $this->assertLessThan(PhpVersion::PHP_10, PhpVersion::PHP_85);
        $this->assertSame(80200, PhpVersion::PHP_82);
    }
}
