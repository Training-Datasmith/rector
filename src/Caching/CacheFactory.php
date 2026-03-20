<?php

declare (strict_types=1);
namespace Rector\Caching;

use Rector\Caching\Value_Object\Storage\File_Cache_Storage;
use Rector\Caching\Value_Object\Storage\Memory_Cache_Storage;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector_Prefix202603\Symfony\Component\Filesystem\Filesystem;
final class Cache_Factory
{
    /**
     * @readonly
     */
    private Filesystem $file_system;
    public function __construct(Filesystem $file_system)
    {
        $this->file_system = $file_system;
    }
    /**
     * @api config factory
     */
    public function create(): \Rector\Caching\Cache
    {
        $cache_directory = Simple_Parameter_Provider::provide_string_parameter(Option::CACHE_DIR);
        $cache_class = File_Cache_Storage::class;
        if (Simple_Parameter_Provider::has_parameter(Option::CACHE_CLASS)) {
            $cache_class = Simple_Parameter_Provider::provide_string_parameter(Option::CACHE_CLASS);
        }
        if ($cache_class === File_Cache_Storage::class) {
            // ensure cache directory exists
            if (!$this->file_system->exists($cache_directory)) {
                $this->file_system->mkdir($cache_directory);
            }
            $file_cache_storage = new File_Cache_Storage($cache_directory, $this->file_system);
            return new \Rector\Caching\Cache($file_cache_storage);
        }
        return new \Rector\Caching\Cache(new Memory_Cache_Storage());
    }
}