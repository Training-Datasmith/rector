<?php

declare(strict_types=1);

namespace Rector\Tests\FileSystem;

use PHPUnit\Framework\TestCase;
use Rector\FileSystem\FileAndDirectoryFilter;

final class FileAndDirectoryFilterTest extends TestCase
{
    public function testSplitsFilesAndDirectoriesPreservingOrder(): void
    {
        $base = sys_get_temp_dir() . '/rector-fadf-' . uniqid('', true);
        mkdir($base);
        $fileA = $base . '/a.txt';
        $dir = $base . '/d';
        $fileB = $base . '/b.txt';
        mkdir($dir);
        file_put_contents($fileA, '');
        file_put_contents($fileB, '');
        $missing = $base . '/missing';
        $filter = new FileAndDirectoryFilter();
        $files = $filter->filterFiles([$fileA, $dir, $fileB, $missing]);
        $dirs = $filter->filterDirectories([$fileA, $dir, $fileB, $missing]);
        $this->assertSame([$fileA, $fileB], $files);
        $this->assertSame([$dir], $dirs);
        unlink($fileA);
        unlink($fileB);
        rmdir($dir);
        rmdir($base);
    }
}
