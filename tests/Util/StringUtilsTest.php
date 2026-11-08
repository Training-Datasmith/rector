<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\Util\StringUtils;

final class StringUtilsTest extends TestCase
{
    public function testMatch(): void
    {
        $this->assertTrue(StringUtils::isMatch('abc', '#a#'));
    }

    public function testNoMatch(): void
    {
        $this->assertFalse(StringUtils::isMatch('abc', '#z#'));
    }
}
