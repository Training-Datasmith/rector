<?php

declare(strict_types=1);

namespace Rector\Tests\Console;

use PHPUnit\Framework\TestCase;
use Rector\Console\ExitCode;

final class ExitCodeTest extends TestCase
{
    public function testCodes(): void
    {
        $this->assertSame(0, ExitCode::SUCCESS);
        $this->assertSame(1, ExitCode::FAILURE);
        $this->assertSame(2, ExitCode::CHANGED_CODE);
    }
}
