<?php

declare (strict_types=1);
namespace Rector\Console;

use Override;
use Rector\Application\Version_Resolver;
use Rector\Changes_Reporting\Output\Console_Output_Formatter;
use Rector\Configuration\Option;
use Rector\Util\Reflection\Privates_Accessor;
use Rector_Prefix202603\Composer\Xdebug_Handler\Xdebug_Handler;
use Rector_Prefix202603\Symfony\Component\Console\Application;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Definition;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Option;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Console_Application extends Application
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @var string
     */
    private const NAME = 'Rector';
    /**
     * @param Command[] $commands
     */
    public function __construct(array $commands, Symfony_Style $symfony_style)
    {
        $this->symfony_style = $symfony_style;
        parent::__construct(self::NAME, Version_Resolver::PACKAGE_VERSION);
        Assert::not_empty($commands);
        Assert::all_is_instance_of($commands, Command::class);
        $this->add_commands($commands);
        // run this command, if no command name is provided
        $this->set_default_command('process');
    }
    #[Override]
    public function do_run(Input_Interface $input, Output_Interface $output): int
    {
        $this->enable_xdebug($input);
        $should_follow_by_newline = \false;
        // skip in this case, since generate content must be clear from meta-info
        if ($this->should_print_meta_information($input)) {
            $output->writeln($this->get_long_version());
            $should_follow_by_newline = \true;
        }
        if ($should_follow_by_newline) {
            $output->write(\PHP_EOL);
        }
        $command_name = $input->get_first_argument();
        if ($command_name === null) {
            return parent::do_run($input, $output);
        }
        // if paths exist or if the command name is not the first argument but with --option, eg:
        // bin/rector src
        // bin/rector --only "RemovePhpVersionIdCheckRector"
        // file_exists() can check directory and file
        if (file_exists($command_name) || isset($_SERVER['argv'][1]) && $command_name !== $_SERVER['argv'][1] && $input->has_parameter_option($_SERVER['argv'][1])) {
            // prepend command name if implicit
            $privates_accessor = new Privates_Accessor();
            $tokens = $privates_accessor->get_private_property($input, 'tokens');
            $tokens = array_merge(['process'], $tokens);
            $privates_accessor->set_private_property($input, 'tokens', $tokens);
        } elseif (!$this->has($command_name)) {
            $this->symfony_style->error(sprintf('The following given path does not match any files or directories: %s%s', "\n\n - ", $command_name));
            return \Rector\Console\Exit_Code::FAILURE;
        }
        return parent::do_run($input, $output);
    }
    #[Override]
    protected function get_default_input_definition(): Input_Definition
    {
        $default_input_definition = parent::get_default_input_definition();
        $this->remove_unused_options($default_input_definition);
        $this->add_custom_options($default_input_definition);
        return $default_input_definition;
    }
    private function should_print_meta_information(Input_Interface $input): bool
    {
        $has_no_arguments = $input->get_first_argument() === null;
        if ($has_no_arguments) {
            return \false;
        }
        $has_version_option = $input->has_parameter_option('--version');
        if ($has_version_option) {
            return \false;
        }
        $output_format = $input->get_parameter_option(['-o', '--output-format']);
        return $output_format === Console_Output_Formatter::NAME;
    }
    private function remove_unused_options(Input_Definition $input_definition): void
    {
        $options = $input_definition->get_options();
        unset($options['quiet'], $options['verbose'], $options['no-interaction']);
        $input_definition->set_options($options);
    }
    private function add_custom_options(Input_Definition $input_definition): void
    {
        $input_definition->add_option(new Input_Option(Option::CONFIG, 'c', Input_Option::VALUE_REQUIRED, 'Path to config file'));
        $input_definition->add_option(new Input_Option(Option::DEBUG, null, Input_Option::VALUE_NONE, 'Enable debug verbosity'));
        $input_definition->add_option(new Input_Option(Option::XDEBUG, null, Input_Option::VALUE_NONE, 'Allow running xdebug'));
        $input_definition->add_option(new Input_Option(Option::CLEAR_CACHE, null, Input_Option::VALUE_NONE, 'Clear cache before starting the execution of the command'));
    }
    private function enable_xdebug(Input_Interface $input): void
    {
        $is_xdebug_allowed = $input->has_parameter_option('--xdebug');
        if (!$is_xdebug_allowed) {
            $xdebug_handler = new Xdebug_Handler('rector');
            $xdebug_handler->set_persistent();
            $xdebug_handler->check();
            unset($xdebug_handler);
        }
    }
}