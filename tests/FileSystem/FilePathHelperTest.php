<?php

declare(strict_types=1);

namespace Rector\Tests\FileSystem;

use PHPUnit\Framework\TestCase;
use Rector\FileSystem\FilePathHelper;
use RectorPrefix202603\Symfony\Component\Filesystem\Filesystem;

final class FilePathHelperTest extends TestCase
{
    private FilePathHelper $helper;

    private string $previousCwd;

    protected function setUp(): void
    {
        $this->helper = new FilePathHelper(new Filesystem());
        $this->previousCwd = getcwd() ?: '/';
    }

    protected function tearDown(): void
    {
        chdir($this->previousCwd);
    }

    public function testCollapsesDotDotInMiddle(): void
    {
        $this->assertSame('foo/baz', $this->helper->normalizePathAndSchema('foo/bar/../baz'));
    }

    public function testCollapsesDoubleSlash(): void
    {
        $this->assertSame('foo/bar', $this->helper->normalizePathAndSchema('foo//bar'));
    }

    public function testDropsDotSegment(): void
    {
        $this->assertSame('foo/bar', $this->helper->normalizePathAndSchema('foo/./bar'));
    }

    public function testPharExitClearsScheme(): void
    {
        $this->assertSame('outside', $this->helper->normalizePathAndSchema('phar://pkg.phar/../outside'));
    }

    public function testPharDotDotAboveRootDoesNotThrow(): void
    {
        $this->assertSame('phar://foo', $this->helper->normalizePathAndSchema('phar://../foo'));
    }

    public function testRelativePathFromCwd(): void
    {
        $dir = sys_get_temp_dir() . '/rector-fph-' . uniqid('', true);
        mkdir($dir);
        $file = $dir . '/sub/file.txt';
        mkdir($dir . '/sub', 0777, true);
        file_put_contents($file, 'x');
        chdir($dir);
        $this->assertSame('sub/file.txt', $this->helper->relativePath($file));
        unlink($file);
        rmdir($dir . '/sub');
        rmdir($dir);
    }
}
