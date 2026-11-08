<?php

declare(strict_types=1);

namespace Rector\Tests\Skipper;

use PHPUnit\Framework\TestCase;
use Rector\Skipper\FileSystem\PathNormalizer;

final class PathNormalizerTest extends TestCase
{
    public function testBackslash(): void
    {
        $this->assertSame('a/b/c', PathNormalizer::normalize('a\\b\\c'));
    }
}
