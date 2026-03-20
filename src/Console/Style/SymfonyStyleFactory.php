<?php

declare (strict_types=1);
namespace Rector\Console\Style;

use Rector\Util\Reflection\Privates_Accessor;
use Rector_Prefix202603\Symfony\Component\Console\Application;
use Rector_Prefix202603\Symfony\Component\Console\Input\Argv_Input;
use Rector_Prefix202603\Symfony\Component\Console\Output\Console_Output;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
final class Symfony_Style_Factory
{
    /**
     * @readonly
     */
    private Privates_Accessor $privates_accessor;
    public function __construct(Privates_Accessor $privates_accessor)
    {
        $this->privates_accessor = $privates_accessor;
    }
    /**
     * @api
     */
    public function create(): \Rector\Console\Style\Rector_Style
    {
        // to prevent missing argv indexes
        if (!isset($_SERVER['argv'])) {
            $_SERVER['argv'] = [];
        }
        $argv_input = new Argv_Input();
        $console_output = new Console_Output();
        // to configure all -v, -vv, -vvv options without memory-lock to Application run() arguments
        $this->privates_accessor->call_private_method(new Application(), 'configureIO', [$argv_input, $console_output]);
        // --debug is called
        if ($argv_input->has_parameter_option('--debug')) {
            $console_output->set_verbosity(Output_Interface::VERBOSITY_DEBUG);
        }
        // disable output for tests
        if ($this->is_php_unit_run()) {
            $console_output->set_verbosity(Output_Interface::VERBOSITY_QUIET);
        }
        return new \Rector\Console\Style\Rector_Style($argv_input, $console_output);
    }
    /**
     * Never ever used static methods if not necessary, this is just handy for tests + src to prevent duplication.
     */
    private function is_php_unit_run(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__');
    }
}