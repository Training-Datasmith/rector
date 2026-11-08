<?php

declare(strict_types=1);

namespace Rector\Tests\ValueObject\Error;

use PHPUnit\Framework\TestCase;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\ValueObject\Error\SystemError;

final class SystemErrorTest extends TestCase
{
    public function testAbsolutePathOfExistingFileIsThatPath(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rector-sys');
        $this->assertIsString($path);
        $error = new SystemError('m', $path);
        $absolute = $error->getAbsoluteFilePath();
        $this->assertIsString($absolute);
        $this->assertSame($path, $absolute);
        @unlink($path);
    }

    public function testMissingRelativePathReturnsNull(): void
    {
        $error = new SystemError('m', 'rector-missing-system-error.php');
        $this->assertNull($error->getAbsoluteFilePath());
    }

    public function testNullRelativePathReturnsNull(): void
    {
        $error = new SystemError('m', null);
        $this->assertNull($error->getAbsoluteFilePath());
    }

    public function testJsonSerializeMissingFileHasNullAbsolutePath(): void
    {
        $error = new SystemError('m', 'rector-missing-system-error.php');
        $json = $error->jsonSerialize();
        $this->assertNull($json['absolute_file_path']);
    }

    public function testDecodeRoundTrip(): void
    {
        $error = new SystemError('msg', 'src/Foo.php', 12, RemoveAndTrueRector::class);
        $decoded = SystemError::decode($error->jsonSerialize());
        $this->assertSame('msg', $decoded->getMessage());
        $this->assertSame('src/Foo.php', $decoded->getRelativeFilePath());
        $this->assertSame(12, $decoded->getLine());
        $this->assertSame(RemoveAndTrueRector::class, $decoded->getRectorClass());
    }

    public function testShortClassFromFqn(): void
    {
        $error = new SystemError('m', null, null, RemoveAndTrueRector::class);
        $this->assertSame('RemoveAndTrueRector', $error->getRectorShortClass());
    }

    public function testShortClassNullAndEmpty(): void
    {
        $this->assertNull((new SystemError('m'))->getRectorShortClass());
        $this->assertNull((new SystemError('m', null, null, ''))->getRectorShortClass());
    }
}
