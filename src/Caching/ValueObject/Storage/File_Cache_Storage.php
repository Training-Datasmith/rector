<?php

declare (strict_types=1);
namespace Rector\Caching\Value_Object\Storage;

use Filesystem_Iterator;
use Rector\Caching\Contract\Value_Object\Storage\Cache_Storage_Interface;
use Rector\Caching\Value_Object\Cache_File_Paths;
use Rector\Caching\Value_Object\Cache_Item;
use Rector\Exception\Cache\Caching_Exception;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Nette\Utils\Random;
/**
 * Inspired by https://github.com/phpstan/phpstan-src/blob/1e7ceae933f07e5a250b61ed94799e6c2ea8daa2/src/Cache/FileCacheStorage.php
 * @see \Rector\Tests\Caching\ValueObject\Storage\FileCacheStorageTest
 */
final class File_Cache_Storage implements Cache_Storage_Interface
{
    /**
     * @readonly
     */
    private string $directory;
    /**
     * @readonly
     */
    private \Rector_Prefix202603\Symfony\Component\Filesystem\Filesystem $filesystem;
    public function __construct(string $directory, \Rector_Prefix202603\Symfony\Component\Filesystem\Filesystem $filesystem)
    {
        $this->directory = $directory;
        $this->filesystem = $filesystem;
    }
    /**
     * @return mixed
     */
    public function load(string $key, string $variable_key)
    {
        return (function (string $key, string $variable_key) {
            $cache_file_paths = $this->get_cache_file_paths($key);
            $file_path = $cache_file_paths->get_file_path();
            if (!\is_file($file_path)) {
                return null;
            }
            $cache_item = require $file_path;
            if (!$cache_item instanceof Cache_Item) {
                return null;
            }
            if (!$cache_item->is_variable_key_valid($variable_key)) {
                return null;
            }
            return $cache_item->get_data();
        })($key, $variable_key);
    }
    /**
     * @param mixed $data
     */
    public function save(string $key, string $variable_key, $data): void
    {
        $cache_file_paths = $this->get_cache_file_paths($key);
        $this->filesystem->mkdir($cache_file_paths->get_first_directory());
        $this->filesystem->mkdir($cache_file_paths->get_second_directory());
        $file_path = $cache_file_paths->get_file_path();
        $tmp_path = \sprintf('%s/%s.tmp', $this->directory, Random::generate());
        $error_before = \error_get_last();
        $exported = @\var_export(new Cache_Item($variable_key, $data), \true);
        $error_after = \error_get_last();
        if ($error_after !== null && $error_before !== $error_after) {
            throw new Caching_Exception(\sprintf('Error occurred while saving item %s (%s) to cache: %s', $key, $variable_key, $error_after['message']));
        }
        // for performance reasons we don't use SmartFileSystem
        File_System::write($tmp_path, \sprintf("<?php declare(strict_types = 1);\n\nreturn %s;", $exported), null);
        $copy_success = @\copy($tmp_path, $file_path);
        @\unlink($tmp_path);
        if ($copy_success) {
            return;
        }
        if (\DIRECTORY_SEPARATOR === '/' || !\file_exists($file_path)) {
            throw new Caching_Exception(\sprintf('Could not write data to cache file %s.', $file_path));
        }
    }
    public function clean(string $key): void
    {
        $cache_file_paths = $this->get_cache_file_paths($key);
        $this->process_remove_cache_file_path($cache_file_paths);
        $this->process_remove_empty_directory($cache_file_paths->get_second_directory());
        $this->process_remove_empty_directory($cache_file_paths->get_first_directory());
    }
    public function clear(): void
    {
        File_System::delete($this->directory);
    }
    private function process_remove_cache_file_path(Cache_File_Paths $cache_file_paths): void
    {
        $file_path = $cache_file_paths->get_file_path();
        if (!$this->filesystem->exists($file_path)) {
            return;
        }
        File_System::delete($file_path);
    }
    private function process_remove_empty_directory(string $directory): void
    {
        if (!$this->filesystem->exists($directory)) {
            return;
        }
        if ($this->is_not_empty_directory($directory)) {
            return;
        }
        File_System::delete($directory);
    }
    private function is_not_empty_directory(string $directory): bool
    {
        // FilesystemIterator will initially point to the first file in the folder - if there are no files in the folder, valid() will return false
        $filesystem_iterator = new Filesystem_Iterator($directory);
        return $filesystem_iterator->valid();
    }
    private function get_cache_file_paths(string $key): Cache_File_Paths
    {
        $key_hash = sha1($key);
        $first_directory = sprintf('%s/%s', $this->directory, substr($key_hash, 0, 2));
        $second_directory = sprintf('%s/%s', $first_directory, (string) substr($key_hash, 2, 2));
        $file_path = sprintf('%s/%s.php', $second_directory, $key_hash);
        return new Cache_File_Paths($first_directory, $second_directory, $file_path);
    }
}