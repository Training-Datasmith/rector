<?php

declare(strict_types=1);

namespace Rector\Tests\Testing;

use PHPUnit\Framework\TestCase;
use Rector\Testing\Fixture\FixtureSplitter;

final class FixtureSplitterTest extends TestCase
{
    public function testContainsSplit(): void
    {
        $this->assertTrue(FixtureSplitter::containsSplit("a\n-----\nb"));
        $this->assertTrue(FixtureSplitter::containsSplit("a\r\n-----\r\nb"));
        $this->assertFalse(FixtureSplitter::containsSplit('-----'));
    }

    public function testSplitCrlf(): void
    {
        $this->assertSame(["left\n", 'right'], FixtureSplitter::splitFixtureFileContents("left\r\n-----\r\nright"));
    }

    public function testSplitFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rector-fix');
        file_put_contents($path, "before\n-----\nafter\n");
        $this->assertSame(["before\n", "after\n"], FixtureSplitter::split($path));
        @unlink($path);
    }
}
