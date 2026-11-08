<?php

declare(strict_types=1);

namespace Rector\Tests\ChangesReporting\Output;

use PHPUnit\Framework\TestCase;
use Rector\ChangesReporting\Output\GitHubOutputFormatter;
use Rector\ChangesReporting\ValueObject\RectorWithLineChange;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\ValueObject\Configuration;
use Rector\ValueObject\ProcessResult;
use Rector\ValueObject\Reporting\FileDiff;

final class GitHubOutputFormatterTest extends TestCase
{
    private const DIFF = "--- Original\n+++ New\n@@ -38,5 +39,6 @@\n-line\n+line\n";

    public function testWorkflowCommandsEncodeNewlinesAndOmitEndLine(): void
    {
        $diff = new FileDiff('src/Foo.php', self::DIFF, 'fmt', [
            new RectorWithLineChange(RemoveAndTrueRector::class, 38),
        ]);
        $result = new ProcessResult([], [$diff], 1);
        $formatter = new GitHubOutputFormatter();
        ob_start();
        $formatter->report($result, new Configuration(false, true, false, 'console', ['php'], [], true));
        $output = ob_get_clean();
        $this->assertStringContainsString('::group::Rector report', $output);
        $this->assertStringContainsString('::endgroup::', $output);
        $this->assertStringContainsString('file=src/Foo.php', $output);
        $this->assertStringContainsString('line=38', $output);
        $this->assertStringContainsString('RemoveAndTrueRector', $output);
        $this->assertStringContainsString('%0A', $output);
        $this->assertStringNotContainsString('endLine=', $output);
    }
}
