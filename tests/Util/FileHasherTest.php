<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\Exception\ShouldNotHappenException;
use Rector\Util\FileHasher;

final class FileHasherTest extends TestCase
{
    private FileHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new FileHasher();
    }

    public function testHashHelloMatchesXxh128Literal(): void
    {
        $this->assertSame('b5e9c1ad071b3e7fc779cfaa5e523818', $this->hasher->hash('hello'));
    }

    public function testHashFilesConcatenatesPerFileDigests(): void
    {
        $f1 = tempnam(sys_get_temp_dir(), 'h1');
        $f2 = tempnam(sys_get_temp_dir(), 'h2');
        file_put_contents($f1, 'alpha');
        file_put_contents($f2, 'beta');
        $expected = hash('xxh128', 'alpha') . hash('xxh128', 'beta');
        $this->assertSame($expected, $this->hasher->hashFiles([$f1, $f2]));
        @unlink($f1);
        @unlink($f2);
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(ShouldNotHappenException::class);
        $this->hasher->hashFiles([sys_get_temp_dir() . '/rector-no-such-file-' . uniqid('', true) . '.txt']);
    }

    public function testEmptyFileListIsEmptyString(): void
    {
        $this->assertSame('', $this->hasher->hashFiles([]));
    }
}
