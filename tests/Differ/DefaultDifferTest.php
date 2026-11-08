<?php

declare(strict_types=1);

namespace Rector\Tests\Differ;

use PHPUnit\Framework\TestCase;
use Rector\Differ\DefaultDiffer;

final class DefaultDifferTest extends TestCase
{
    private DefaultDiffer $differ;

    protected function setUp(): void
    {
        $this->differ = new DefaultDiffer();
    }

    public function testLabelsAndChangedTokens(): void
    {
        $diff = $this->differ->diff("alpha\n", "beta\n");
        $this->assertStringContainsString('Original', $diff);
        $this->assertStringContainsString('New', $diff);
        $this->assertStringContainsString('alpha', $diff);
        $this->assertStringContainsString('beta', $diff);
    }

    public function testIdenticalIsEmpty(): void
    {
        $this->assertSame('', $this->differ->diff('same', 'same'));
    }
}
