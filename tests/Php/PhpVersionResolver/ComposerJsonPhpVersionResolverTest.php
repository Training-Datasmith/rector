<?php

declare(strict_types=1);

namespace Rector\Tests\Php\PhpVersionResolver;

use PHPUnit\Framework\TestCase;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Php\PhpVersionResolver\ComposerJsonPhpVersionResolver;
use Rector\Tests\Support\StaticStateReset;
use Rector\ValueObject\PhpVersion;

final class ComposerJsonPhpVersionResolverTest extends TestCase
{
    protected function setUp(): void
    {
        StaticStateReset::reset();
    }

    protected function tearDown(): void
    {
        StaticStateReset::reset();
    }

    public function testOrConstraintLowerBoundIs74(): void
    {
        $path = $this->writeComposer('{"require":{"php":"^7.4|^8.0"}}');
        $this->assertSame(PhpVersion::PHP_74, ComposerJsonPhpVersionResolver::resolve($path));
    }

    public function testExactVersionIgnoresPatch(): void
    {
        $path = $this->writeComposer('{"require":{"php":"8.2.9"}}');
        $this->assertSame(PhpVersion::PHP_82, ComposerJsonPhpVersionResolver::resolve($path));
    }

    public function testPlatformPhp(): void
    {
        $path = $this->writeComposer('{"require":{},"config":{"platform":{"php":"8.1.20"}}}');
        $this->assertSame(PhpVersion::PHP_81, ComposerJsonPhpVersionResolver::resolve($path));
    }

    public function testMissingPhpReturnsNull(): void
    {
        $path = $this->writeComposer('{"require":{}}');
        $this->assertNull(ComposerJsonPhpVersionResolver::resolve($path));
    }

    public function testCacheIgnoresLaterFileChange(): void
    {
        $path = $this->writeComposer('{"require":{"php":"8.0.0"}}');
        $first = ComposerJsonPhpVersionResolver::resolve($path);
        file_put_contents($path, '{"require":{"php":"8.3.1"}}');
        $second = ComposerJsonPhpVersionResolver::resolve($path);
        $this->assertSame($first, $second);
    }

    public function testCwdWithoutComposerThrows(): void
    {
        $dir = sys_get_temp_dir() . '/rector-cwd-' . uniqid('', true);
        mkdir($dir);
        $prev = getcwd();
        chdir($dir);
        try {
            $this->expectException(InvalidConfigurationException::class);
            ComposerJsonPhpVersionResolver::resolveFromCwdOrFail();
        } finally {
            chdir($prev ?: '/');
            rmdir($dir);
        }
    }

    public function testCwdWithComposerResolves(): void
    {
        $dir = sys_get_temp_dir() . '/rector-cwd-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/composer.json', '{"require":{"php":"8.0.1"}}');
        $prev = getcwd();
        chdir($dir);
        try {
            $this->assertSame(PhpVersion::PHP_80, ComposerJsonPhpVersionResolver::resolveFromCwdOrFail());
        } finally {
            chdir($prev ?: '/');
            unlink($dir . '/composer.json');
            rmdir($dir);
        }
    }

    private function writeComposer(string $json): string
    {
        $path = tempnam(sys_get_temp_dir(), 'rector-cj');
        file_put_contents($path, $json);
        return $path;
    }
}
