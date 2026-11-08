<?php

declare(strict_types=1);

namespace Rector\Tests\Skipper;

use PHPUnit\Framework\TestCase;
use Rector\Skipper\RealpathMatcher;

final class RealpathMatcherTest extends TestCase
{
    private RealpathMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new RealpathMatcher();
    }

    public function testDirectoryPrefix(): void
    {
        $root = sys_get_temp_dir() . '/rector-rpm-' . uniqid('', true);
        mkdir($root);
        mkdir($root . '/nested');
        $file = $root . '/nested/file.txt';
        file_put_contents($file, '');
        $this->assertTrue($this->matcher->match($root, $file));
        unlink($file);
        rmdir($root . '/nested');
        rmdir($root);
    }

    public function testSiblingPrefixDoesNotMatch(): void
    {
        $root = sys_get_temp_dir() . '/rector-rpm-' . uniqid('', true);
        $extra = $root . '-extra';
        mkdir($root);
        mkdir($extra);
        $file = $extra . '/file.txt';
        file_put_contents($file, '');
        $this->assertFalse($this->matcher->match($root, $file));
        unlink($file);
        rmdir($extra);
        rmdir($root);
    }

    public function testMissingPathIsFalse(): void
    {
        $root = sys_get_temp_dir() . '/rector-rpm-' . uniqid('', true);
        mkdir($root);
        $file = $root . '/nested/file.txt';
        $fileReal = realpath($root);
        $this->assertIsString($fileReal);
        $this->assertFalse($this->matcher->match($file, $root));
        rmdir($root);
    }
}
