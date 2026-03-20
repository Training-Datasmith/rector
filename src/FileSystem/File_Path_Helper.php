<?php

declare (strict_types=1);
namespace Rector\File_System;

use Rector\Skipper\File_System\Path_Normalizer;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symfony\Component\Filesystem\Filesystem;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\FileSystem\FilePathHelperTest
 */
final class File_Path_Helper
{
    /**
     * @readonly
     */
    private Filesystem $filesystem;
    /**
     * @see https://regex101.com/r/d4F5Fm/1
     * @var string
     */
    private const SCHEME_PATH_REGEX = '#^([a-z]+)\:\/\/(.+)#';
    /**
     * @see https://regex101.com/r/no28vw/1
     * @var string
     */
    private const TWO_AND_MORE_SLASHES_REGEX = '#/{2,}#';
    /**
     * @var string
     */
    private const SCHEME_UNDEFINED = 'undefined';
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    public function relative_path(string $file_real_path): string
    {
        if (!$this->filesystem->is_absolute_path($file_real_path)) {
            return $file_real_path;
        }
        return $this->relative_file_path_from_directory($file_real_path, getcwd());
    }
    /**
     * Used from
     * https://github.com/phpstan/phpstan-src/blob/02425e61aa48f0668b4efb3e73d52ad544048f65/src/File/FileHelper.php#L40, with custom modifications
     */
    public function normalize_path_and_schema(string $original_path): string
    {
        $directory_separator = \DIRECTORY_SEPARATOR;
        $matches = Strings::match($original_path, self::SCHEME_PATH_REGEX);
        if ($matches !== null) {
            [, $scheme, $path] = $matches;
        } else {
            $scheme = self::SCHEME_UNDEFINED;
            $path = $original_path;
        }
        $normalized_path = Path_Normalizer::normalize((string) $path);
        $path = Strings::replace($normalized_path, self::TWO_AND_MORE_SLASHES_REGEX, '/');
        $path_root = strncmp($path, '/', strlen('/')) === 0 ? $directory_separator : '';
        $path_parts = explode('/', trim($path, '/'));
        /** @var string $scheme */
        $normalized_path_parts = $this->normalize_path_parts($path_parts, $scheme);
        $path_start = $scheme !== self::SCHEME_UNDEFINED ? $scheme . '://' : '';
        return Path_Normalizer::normalize($path_start . $path_root . implode($directory_separator, $normalized_path_parts));
    }
    private function relative_file_path_from_directory(string $file_real_path, string $directory): string
    {
        Assert::directory($directory);
        $normalized_file_real_path = Path_Normalizer::normalize($file_real_path);
        $relative_file_path = $this->filesystem->make_path_relative($normalized_file_real_path, $directory);
        return rtrim($relative_file_path, '/');
    }
    /**
     * @param string[] $pathParts
     * @return string[]
     */
    private function normalize_path_parts(array $path_parts, string $scheme): array
    {
        $normalized_path_parts = [];
        foreach ($path_parts as $path_part) {
            if ($path_part === '.') {
                continue;
            }
            if ($path_part !== '..') {
                $normalized_path_parts[] = $path_part;
                continue;
            }
            /** @var string $removedPart */
            $removed_part = array_pop($normalized_path_parts);
            if ($scheme !== 'phar') {
                continue;
            }
            if (substr_compare($removed_part, '.phar', -strlen('.phar')) !== 0) {
                continue;
            }
            $scheme = self::SCHEME_UNDEFINED;
        }
        return $normalized_path_parts;
    }
}