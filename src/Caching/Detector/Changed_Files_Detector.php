<?php

declare (strict_types=1);
namespace Rector\Caching\Detector;

use Rector\Caching\Cache;
use Rector\Caching\Config\File_Hash_Computer;
use Rector\Caching\Enum\Cache_Key;
use Rector\Util\File_Hasher;
/**
 * Inspired by https://github.com/symplify/symplify/pull/90/files#diff-72041b2e1029a08930e13d79d298ef11
 *
 * @see \Rector\Tests\Caching\Detector\ChangedFilesDetectorTest
 */
final class Changed_Files_Detector
{
    /**
     * @readonly
     */
    private File_Hash_Computer $file_hash_computer;
    /**
     * @readonly
     */
    private Cache $cache;
    /**
     * @readonly
     */
    private File_Hasher $file_hasher;
    /**
     * @var array<string, true>
     */
    private array $cacheable_files = [];
    public function __construct(File_Hash_Computer $file_hash_computer, Cache $cache, File_Hasher $file_hasher)
    {
        $this->file_hash_computer = $file_hash_computer;
        $this->cache = $cache;
        $this->file_hasher = $file_hasher;
    }
    public function cache_file(string $file_path): void
    {
        $file_path_cache_key = $this->get_file_path_cache_key($file_path);
        if (!isset($this->cacheable_files[$file_path_cache_key])) {
            return;
        }
        $hash = $this->hash_file($file_path);
        $this->cache->save($file_path_cache_key, Cache_Key::FILE_HASH_KEY, $hash);
    }
    public function add_cacheable_file(string $file_path): void
    {
        $file_path_cache_key = $this->get_file_path_cache_key($file_path);
        $this->cacheable_files[$file_path_cache_key] = \true;
    }
    public function has_file_changed(string $file_path): bool
    {
        $file_info_cache_key = $this->get_file_path_cache_key($file_path);
        $cached_value = $this->cache->load($file_info_cache_key, Cache_Key::FILE_HASH_KEY);
        if ($cached_value !== null) {
            $current_file_hash = $this->hash_file($file_path);
            return $current_file_hash !== $cached_value;
        }
        // we don't have a value to compare against. Be defensive and assume its changed
        return \true;
    }
    public function invalidate_file(string $file_path): void
    {
        $file_info_cache_key = $this->get_file_path_cache_key($file_path);
        $this->cache->clean($file_info_cache_key);
        unset($this->cacheable_files[$file_info_cache_key]);
    }
    public function clear(): void
    {
        $this->cache->clear();
    }
    /**
     * @api
     */
    public function set_first_resolved_config_file_info(string $file_path): void
    {
        // the first config is core to all → if it was changed, just invalidate it
        $config_hash = $this->file_hash_computer->compute($file_path);
        $this->store_configuration_data_hash($file_path, $config_hash);
    }
    private function resolve_path(string $file_path): string
    {
        $real_path = realpath($file_path);
        if ($real_path === \false) {
            return $file_path;
        }
        return $real_path;
    }
    private function get_file_path_cache_key(string $file_path): string
    {
        return $this->file_hasher->hash($this->resolve_path($file_path));
    }
    private function hash_file(string $file_path): string
    {
        return $this->file_hasher->hash_files([$this->resolve_path($file_path)]);
    }
    private function store_configuration_data_hash(string $file_path, string $configuration_hash): void
    {
        $key = Cache_Key::CONFIGURATION_HASH_KEY . '_' . $this->get_file_path_cache_key($file_path);
        $this->invalidate_cache_if_configuration_changed($key, $configuration_hash);
        $this->cache->save($key, Cache_Key::CONFIGURATION_HASH_KEY, $configuration_hash);
    }
    private function invalidate_cache_if_configuration_changed(string $key, string $configuration_hash): void
    {
        $old_cached_value = $this->cache->load($key, Cache_Key::CONFIGURATION_HASH_KEY);
        if ($old_cached_value === null) {
            return;
        }
        if ($old_cached_value === $configuration_hash) {
            return;
        }
        // should be unique per getcwd()
        $this->clear();
    }
}