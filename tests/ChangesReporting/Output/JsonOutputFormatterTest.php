<?php

declare(strict_types=1);

namespace Rector\Tests\ChangesReporting\Output;

use PHPUnit\Framework\TestCase;
use Rector\ChangesReporting\Output\JsonOutputFormatter;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\ValueObject\Configuration;
use Rector\ValueObject\Error\SystemError;
use Rector\ValueObject\ProcessResult;
use Rector\ValueObject\Reporting\FileDiff;

final class JsonOutputFormatterTest extends TestCase
{
    public function testReportParsedFields(): void
    {
        $diff = new FileDiff('src/Foo.php', 'diff-body', 'console', []);
        $result = new ProcessResult([], [$diff], 1);
        $formatter = new JsonOutputFormatter();
        ob_start();
        $formatter->report($result, new Configuration());
        $output = ob_get_clean();
        $this->assertIsString($output);
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertSame(1, $decoded['totals']['changed_files']);
        $this->assertSame('src/Foo.php', $decoded['file_diffs'][0]['file']);
        $this->assertSame('diff-body', $decoded['file_diffs'][0]['diff']);
        $this->assertSame([], $decoded['file_diffs'][0]['applied_rectors']);
        $this->assertContains('src/Foo.php', $decoded['changed_files']);
        $this->assertSame(0, $decoded['totals']['errors']);
    }

    public function testSystemErrorFields(): void
    {
        $error = new SystemError('boom', 'src/Bar.php', 9, RemoveAndTrueRector::class);
        $result = new ProcessResult([$error], [], 0);
        $formatter = new JsonOutputFormatter();
        ob_start();
        $formatter->report($result, new Configuration());
        $output = ob_get_clean();
        $decoded = json_decode($output, true);
        $this->assertSame(1, $decoded['totals']['errors']);
        $this->assertSame('boom', $decoded['errors'][0]['message']);
        $this->assertSame('src/Bar.php', $decoded['errors'][0]['file']);
        $this->assertSame(9, $decoded['errors'][0]['line']);
        $this->assertSame(RemoveAndTrueRector::class, $decoded['errors'][0]['caused_by']);
    }
}
