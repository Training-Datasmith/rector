<?php

declare(strict_types=1);

namespace Rector\Tests\Skipper;

use PHPUnit\Framework\TestCase;
use Rector\Skipper\Fnmatcher;

final class FnmatcherTest extends TestCase
{
    private Fnmatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new Fnmatcher();
    }

    public function testDirectAndRelativeStar(): void
    {
        $this->assertTrue($this->matcher->match('*.php', 'Foo.php'));
        $this->assertTrue($this->matcher->match('*.php', 'src/Foo.php'));
    }

    public function testNonMatch(): void
    {
        $this->assertFalse($this->matcher->match('*.php', 'Foo.php.bak'));
    }
}
