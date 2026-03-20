<?php

declare (strict_types=1);
namespace Rector\Caching\Value_Object\Storage;

use Rector\Caching\Contract\Value_Object\Storage\Cache_Storage_Interface;
use Rector\Caching\Value_Object\Cache_Item;
/**
 * inspired by https://github.com/phpstan/phpstan-src/blob/560652088406d7461c2c4ad4897784e33f8ab312/src/Cache/MemoryCacheStorage.php
 */
final class Memory_Cache_Storage implements Cache_Storage_Interface
{
    /**
     * @var array<string, CacheItem>
     */
    private array $storage = [];
    /**
     * @return null|mixed
     */
    public function load(string $key, string $variable_key)
    {
        if (!isset($this->storage[$key])) {
            return null;
        }
        $item = $this->storage[$key];
        if (!$item->is_variable_key_valid($variable_key)) {
            return null;
        }
        return $item->get_data();
    }
    /**
     * @param mixed $data
     */
    public function save(string $key, string $variable_key, $data): void
    {
        $this->storage[$key] = new Cache_Item($variable_key, $data);
    }
    public function clean(string $key): void
    {
        if (!isset($this->storage[$key])) {
            return;
        }
        unset($this->storage[$key]);
    }
    public function clear(): void
    {
        $this->storage = [];
    }
}