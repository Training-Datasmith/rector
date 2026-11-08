<?php

declare(strict_types=1);

namespace Rector\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Rector\ValueObject\Error\SystemError;
use Rector\ValueObject\ProcessResult;
use Rector\ValueObject\Reporting\FileDiff;

final class ProcessResultTest extends TestCase
{
    public function testDefaultFileDiffsDropEmptyDiff(): void
    {
        $empty = new FileDiff('a.php', '', '');
        $with = new FileDiff('b.php', '---', '');
        $result = new ProcessResult([], [$empty, $with], 1);
        $this->assertCount(1, $result->getFileDiffs());
        $diffs = array_values($result->getFileDiffs());
        $this->assertSame('b.php', $diffs[0]->getRelativeFilePath());
    }

    public function testFileDiffsKeepEmptyWhenAsked(): void
    {
        $empty = new FileDiff('a.php', '', '');
        $result = new ProcessResult([], [$empty], 0);
        $this->assertCount(1, $result->getFileDiffs(false));
    }

    public function testAddSystemErrorsAppends(): void
    {
        $one = new SystemError('e1');
        $two = new SystemError('e2');
        $three = new SystemError('e3');
        $result = new ProcessResult([$one], [], 0);
        $result->addSystemErrors([$two, $three]);
        $this->assertCount(3, $result->getSystemErrors());
    }

    public function testRejectsNonError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProcessResult([new \stdClass()], [], 0);
    }

    public function testTotalChanged(): void
    {
        $result = new ProcessResult([], [], 4);
        $this->assertSame(4, $result->getTotalChanged());
    }
}
