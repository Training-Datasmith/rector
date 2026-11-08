<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\DeadCode\Rector\If_\RemoveDeadIfBlockRector;
use Rector\PostRector\Rector\ClassRenamingPostRector;
use Rector\Util\RectorClassesSorter;

final class RectorClassesSorterTest extends TestCase
{
    public function testSortsAndDropsPostRector(): void
    {
        $input = [
            RemoveDeadIfBlockRector::class,
            RemoveAndTrueRector::class,
            RemoveAndTrueRector::class,
            ClassRenamingPostRector::class,
        ];
        $sorted = RectorClassesSorter::sortAndFilterOutPostRectors($input);
        $this->assertSame([RemoveAndTrueRector::class, RemoveDeadIfBlockRector::class], $sorted);
    }
}
