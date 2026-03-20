<?php

declare (strict_types=1);
namespace Rector\Testing\Php_Unit;

use Php_Unit\Framework\Test_Case;
use Php_Unit\Runner\Version;
use Rector\Config\Rector_Config;
use Rector\Dependency_Injection\Lazy_Container_Factory;
abstract class Abstract_Lazy_Test_Case extends Test_Case
{
    protected static ?Rector_Config $rector_config = null;
    protected function set_up(): void
    {
        // this is needed to have always the same preloaded nikic/php-parser classes
        // in both bare AbstractLazyTestCase lazy tests and AbstractRectorTestCase tests
        $this->include_preload_files_and_scoper_autoload();
    }
    /**
     * @api
     * @param string[] $configFiles
     */
    protected function boot_from_config_files(array $config_files): void
    {
        $rector_config = self::get_container();
        foreach ($config_files as $config_file) {
            $rector_config->import($config_file);
        }
    }
    /**
     * @template TType as object
     * @param class-string<TType> $class
     * @return TType
     */
    protected function make(string $class): object
    {
        return self::get_container()->make($class);
    }
    protected static function get_container(): Rector_Config
    {
        if (!self::$rector_config instanceof Rector_Config) {
            $lazy_container_factory = new Lazy_Container_Factory();
            self::$rector_config = $lazy_container_factory->create();
        }
        self::$rector_config->boot();
        return self::$rector_config;
    }
    protected function is_windows(): bool
    {
        return strncasecmp(\PHP_OS, 'WIN', 3) === 0;
    }
    private function include_preload_files_and_scoper_autoload(): void
    {
        if (file_exists(__DIR__ . '/../../../preload.php')) {
            if (file_exists(__DIR__ . '/../../../vendor')) {
                /**
                 * On PHPUnit 12+, when classmap autoloaded, it means preload already loaded early
                 */
                if (!class_exists(Version::class, \true) || (int) Version::id() < 12) {
                    require_once __DIR__ . '/../../../preload.php';
                }
                // test case in rector split package
            } elseif (file_exists(__DIR__ . '/../../../../../../vendor')) {
                require_once __DIR__ . '/../../../preload-split-package.php';
            }
        }
        if (\file_exists(__DIR__ . '/../../../vendor/scoper-autoload.php')) {
            require_once __DIR__ . '/../../../vendor/scoper-autoload.php';
        }
    }
}