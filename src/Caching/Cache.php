<?php

declare (strict_types=1);
namespace Rector\Caching;

use Rector\Caching\Contract\Value_Object\Storage\Cache_Storage_Interface;
use Rector\Caching\Enum\Cache_Key;
final class Cache
{
    /**
     * @readonly
     */
    private Cache_Storage_Interface $cache_storage;
    public function __construct(Cache_Storage_Interface $cache_storage)
    {
        $this->cache_storage = $cache_storage;
    }
    /**
     * @param CacheKey::* $variableKey
     * @return mixed|null
     */
    public function load(string $key, string $variable_key)
    {
        return $this->cache_storage->load($key, $variable_key);
    }
    /**
     * @param CacheKey::* $variableKey
     * @param mixed $data
     */
    public function save(string $key, string $variable_key, $data): void
    {
        $this->cache_storage->save($key, $variable_key, $data);
    }
    public function clear(): void
    {
        $this->cache_storage->clear();
    }
    public function clean(string $cache_key): void
    {
        $this->cache_storage->clean($cache_key);
    }
}