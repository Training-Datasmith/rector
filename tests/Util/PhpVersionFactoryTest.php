<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\Util\PhpVersionFactory;
use Rector\ValueObject\PhpVersion;

final class PhpVersionFactoryTest extends TestCase
{
    public function testTwoPartVersion(): void
    {
        $this->assertSame(PhpVersion::PHP_82, PhpVersionFactory::createIntVersion('8.2'));
    }

    public function testPatchIgnored(): void
    {
        $this->assertSame(PhpVersion::PHP_82, PhpVersionFactory::createIntVersion('8.2.14'));
    }

    public function testStabilitySuffixIgnored(): void
    {
        $this->assertSame(PhpVersion::PHP_74, PhpVersionFactory::createIntVersion('7.4.33-extra'));
    }

    public function testEightZero(): void
    {
        $this->assertSame(PhpVersion::PHP_80, PhpVersionFactory::createIntVersion('8.0'));
    }
}
