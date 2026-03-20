<?php

declare (strict_types=1);
namespace Rector\Autoloading;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Static_Reflection\Dynamic_Source_Locator_Decorator;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * Should it pass autoload files/directories to PHPStan analyzer?
 */
final class Additional_Autoloader
{
    /**
     * @readonly
     */
    private Dynamic_Source_Locator_Decorator $dynamic_source_locator_decorator;
    public function __construct(Dynamic_Source_Locator_Decorator $dynamic_source_locator_decorator)
    {
        $this->dynamic_source_locator_decorator = $dynamic_source_locator_decorator;
    }
    public function autoload_input(Input_Interface $input): void
    {
        if (!$input->has_option(Option::AUTOLOAD_FILE)) {
            return;
        }
        /** @var string|null $autoloadFile */
        $autoload_file = $input->get_option(Option::AUTOLOAD_FILE);
        if ($autoload_file === null) {
            return;
        }
        Assert::file_exists($autoload_file, sprintf('Extra autoload file %s was not found', $autoload_file));
        require_once $autoload_file;
    }
    public function autoload_paths(): void
    {
        $autoload_paths = Simple_Parameter_Provider::provide_array_parameter(Option::AUTOLOAD_PATHS);
        $autoload_paths = $this->dynamic_source_locator_decorator->add_paths($autoload_paths);
        // set values of Option::AUTOLOAD_PATHS with transformed paths
        Simple_Parameter_Provider::set_parameter(Option::AUTOLOAD_PATHS, $autoload_paths);
    }
}