<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\Util\NewLineSplitter;

final class NewLineSplitterTest extends TestCase
{
    public function testLf(): void
    {
        $this->assertSame(['a', 'b', ''], NewLineSplitter::split("a\nb\n"));
    }

    public function testCrlf(): void
    {
        $this->assertSame(['a', 'b'], NewLineSplitter::split("a\r\nb"));
    }

    public function testBlankLine(): void
    {
        $this->assertSame(['a', '', 'b'], NewLineSplitter::split("a\r\n\r\nb"));
    }

    public function testNoNewline(): void
    {
        $this->assertSame(['only'], NewLineSplitter::split('only'));
    }
}
