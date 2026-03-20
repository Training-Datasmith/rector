<?php

declare (strict_types=1);
namespace Rector\Autoloading;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector_Prefix202603\Webmozart\Assert\Assert;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use Spl_File_Info;
/**
 * @see \Rector\Tests\Autoloading\BootstrapFilesIncluderTest
 */
final class Bootstrap_Files_Includer
{
    /**
     * Inspired by
     * @see https://github.com/phpstan/phpstan-src/commit/aad1bf888ab7b5808898ee5fe2228bb8bb4e4cf1
     */
    public function include_bootstrap_files(): void
    {
        $bootstrap_files = Simple_Parameter_Provider::provide_array_parameter(Option::BOOTSTRAP_FILES);
        Assert::all_string($bootstrap_files);
        /** @var string[] $bootstrapFiles */
        foreach ($bootstrap_files as $bootstrap_file) {
            if (!is_file($bootstrap_file)) {
                throw new Should_Not_Happen_Exception(sprintf('Bootstrap file "%s" does not exist.', $bootstrap_file));
            }
            require $bootstrap_file;
        }
        $this->require_rector_stubs();
    }
    private function require_rector_stubs(): void
    {
        $stubs_rector_directory = realpath(__DIR__ . '/../../stubs-rector');
        if ($stubs_rector_directory === \false) {
            return;
        }
        $dir = new Recursive_Directory_Iterator($stubs_rector_directory, Recursive_Directory_Iterator::SKIP_DOTS);
        $stubs = new Recursive_Iterator_Iterator($dir);
        foreach ($stubs as $stub) {
            /** @var SplFileInfo $stub */
            require_once $stub->get_real_path();
        }
    }
}