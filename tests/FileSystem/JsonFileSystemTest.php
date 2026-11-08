<?php

declare(strict_types=1);

namespace Rector\Tests\FileSystem;

use PHPUnit\Framework\TestCase;
use Rector\FileSystem\JsonFileSystem;
use RectorPrefix202603\Nette\Utils\JsonException;

final class JsonFileSystemTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rector-json');
        $data = ['name' => 'café', 'n' => 2, 'nested' => ['ok' => true]];
        JsonFileSystem::writeFile($path, $data);
        $this->assertSame($data, JsonFileSystem::readFilePath($path));
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString('"name"', $contents);
        @unlink($path);
    }

    public function testInvalidJsonThrows(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rector-json-bad');
        file_put_contents($path, '{');
        $this->expectException(JsonException::class);
        try {
            JsonFileSystem::readFilePath($path);
        } finally {
            @unlink($path);
        }
    }
}
