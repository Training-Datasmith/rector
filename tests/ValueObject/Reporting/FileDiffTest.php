<?php

declare(strict_types=1);

namespace Rector\Tests\ValueObject\Reporting;

use PHPUnit\Framework\TestCase;
use Rector\ChangesReporting\ValueObject\RectorWithLineChange;
use Rector\Parallel\ValueObject\BridgeItem;
use RectorPrefix202603\Nette\Utils\Json;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\PostRector\Rector\ClassRenamingPostRector;
use Rector\ValueObject\Reporting\FileDiff;

final class FileDiffTest extends TestCase
{
    private const SAMPLE_DIFF = "--- Original\n+++ New\n@@ -38,5 +39,6 @@\nreturn true;\n}\n";

    public function testFirstLineFromHunk(): void
    {
        $fileDiff = new FileDiff('some/file.php', self::SAMPLE_DIFF, 'fmt');
        $this->assertSame(38, $fileDiff->getFirstLineNumber());
    }

    public function testLastLineIsFirstPlusRange(): void
    {
        $fileDiff = new FileDiff('some/file.php', self::SAMPLE_DIFF, 'fmt');
        $this->assertSame(43, $fileDiff->getLastLineNumber());
    }

    public function testLastLineEqualsFirstWhenRangeOmitted(): void
    {
        $diff = "--- Original\n+++ New\n@@ -38 +39 @@\nreturn true;\n}\n";
        $fileDiff = new FileDiff('some/file.php', $diff, 'fmt');
        $this->assertSame(38, $fileDiff->getLastLineNumber());
    }

    public function testInvalidHunkLineNumbersAreNull(): void
    {
        $diff = "--- Original\n+++ New\n@@@@\nreturn true;\n}\n";
        $fileDiff = new FileDiff('some/file.php', $diff, 'fmt');
        $this->assertNull($fileDiff->getFirstLineNumber());
        $this->assertNull($fileDiff->getLastLineNumber());
    }

    public function testAbsolutePathMissingIsNull(): void
    {
        $fileDiff = new FileDiff('missing-path.php', '', '');
        $this->assertNull($fileDiff->getAbsoluteFilePath());
    }

    public function testAbsolutePathExistingIsString(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rector-fd');
        $this->assertIsString($path);
        $fileDiff = new FileDiff($path, '', '');
        $absolute = $fileDiff->getAbsoluteFilePath();
        $this->assertIsString($absolute);
        $this->assertSame($path, $absolute);
        @unlink($path);
    }

    public function testShortClassAndPostRectorFiltered(): void
    {
        $changes = [
            new RectorWithLineChange(RemoveAndTrueRector::class, 1),
            new RectorWithLineChange(ClassRenamingPostRector::class, 2),
            new RectorWithLineChange(RemoveAndTrueRector::class, 3),
        ];
        $fileDiff = new FileDiff('f.php', 'd', 'c', $changes);
        $this->assertSame(['RemoveAndTrueRector'], $fileDiff->getRectorShortClasses());
    }

    public function testDecodeRoundTrip(): void
    {
        $fileDiff = new FileDiff(
            'src/Foo.php',
            'diff-text',
            'console',
            [new RectorWithLineChange(RemoveAndTrueRector::class, 10)]
        );
        $payload = $fileDiff->jsonSerialize();
        $payload[BridgeItem::RECTORS_WITH_LINE_CHANGES] = array_map(
            static fn (RectorWithLineChange $change): array => $change->jsonSerialize(),
            $payload[BridgeItem::RECTORS_WITH_LINE_CHANGES]
        );
        $decoded = FileDiff::decode(Json::decode(Json::encode($payload), true));
        $this->assertSame('src/Foo.php', $decoded->getRelativeFilePath());
        $this->assertSame('diff-text', $decoded->getDiff());
        $this->assertSame('console', $decoded->getDiffConsoleFormatted());
        $changes = $decoded->getRectorChanges();
        $this->assertCount(1, $changes);
        $this->assertSame(RemoveAndTrueRector::class, $changes[0]->getRectorClass());
        $this->assertSame(10, $changes[0]->jsonSerialize()['line']);
    }
}
