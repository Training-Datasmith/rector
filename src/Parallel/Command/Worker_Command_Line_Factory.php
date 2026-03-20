<?php

declare (strict_types=1);
namespace Rector\Parallel\Command;

use Rector\Changes_Reporting\Output\Json_Output_Formatter;
use Rector\Configuration\Option;
use Rector\File_System\File_Path_Helper;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symplify\Easy_Parallel\Exception\Parallel_Should_Not_Happen_Exception;
use Rector_Prefix202603\Symplify\Easy_Parallel\Reflection\Command_From_Reflection_Factory;
/**
 * @see \Rector\Tests\Parallel\Command\WorkerCommandLineFactoryTest
 * @todo possibly extract to symplify/easy-parallel
 */
final class Worker_Command_Line_Factory
{
    /**
     * @readonly
     */
    private Command_From_Reflection_Factory $command_from_reflection_factory;
    /**
     * @readonly
     */
    private File_Path_Helper $file_path_helper;
    /**
     * @var string
     */
    private const OPTION_DASHES = '--';
    public function __construct(Command_From_Reflection_Factory $command_from_reflection_factory, File_Path_Helper $file_path_helper)
    {
        $this->command_from_reflection_factory = $command_from_reflection_factory;
        $this->file_path_helper = $file_path_helper;
    }
    /**
     * @param class-string<Command> $mainCommandClass
     */
    public function create(string $main_script, string $main_command_class, string $worker_command_name, Input_Interface $input, string $identifier, int $port): string
    {
        $command_arguments = array_slice($_SERVER['argv'], 1);
        // add implicit "process" command name if missing
        if ($command_arguments !== [] && ($command_arguments[0] !== 'process' && $command_arguments[0] !== 'p') && !defined('PHPUNIT_COMPOSER_INSTALL')) {
            $command_arguments = array_merge(['process'], $command_arguments);
        }
        $args = array_merge([\PHP_BINARY, $main_script], $command_arguments);
        $worker_command_array = [];
        $main_command = $this->command_from_reflection_factory->create($main_command_class);
        if ($main_command->get_name() === null) {
            $error_message = sprintf('The command name for "%s" is missing', get_class($main_command));
            throw new Parallel_Should_Not_Happen_Exception($error_message);
        }
        $main_command_name = $main_command->get_name();
        $main_command_names = [$main_command_name, $main_command_name[0]];
        foreach ($args as $arg) {
            // skip command name
            if (in_array($arg, $main_command_names, \true)) {
                break;
            }
            $worker_command_array[] = escapeshellarg((string) $arg);
        }
        $worker_command_array[] = $worker_command_name;
        $main_command_option_names = $this->get_command_option_names($main_command);
        $worker_command_options = $this->mirror_command_options($input, $main_command_option_names);
        $worker_command_array = array_merge($worker_command_array, $worker_command_options);
        // for TCP local server
        $worker_command_array[] = '--port';
        $worker_command_array[] = $port;
        $worker_command_array[] = '--identifier';
        $worker_command_array[] = escapeshellarg($identifier);
        /** @var string[] $paths */
        $paths = $input->get_argument(Option::SOURCE);
        foreach ($paths as $path) {
            $worker_command_array[] = escapeshellarg($path);
        }
        // set json output
        $worker_command_array[] = self::OPTION_DASHES . Option::OUTPUT_FORMAT;
        $worker_command_array[] = escapeshellarg(Json_Output_Formatter::NAME);
        // disable colors, breaks json_decode() otherwise
        // @see https://github.com/symfony/symfony/issues/1238
        $worker_command_array[] = '--no-ansi';
        // Only pass --config if explicitly set via command line
        // If not set, the worker will resolve config using RectorConfigsResolver fallback mechanism
        if ($input->has_option(Option::CONFIG)) {
            $config_value = $input->get_option(Option::CONFIG);
            if (is_string($config_value) && $config_value !== '') {
                $worker_command_array[] = '--config';
                /**
                 * On parallel, the command is generated with `--config` addition
                 * Using escapeshellarg() to ensure the --config path escaped, even when it has a space.
                 *
                 * eg:
                 *    --config /path/e2e/parallel with space/rector.php
                 *
                 * that can cause error:
                 *
                 *    File /rector-src/e2e/parallel\" was not found
                 *
                 * the escaped result is:
                 *
                 *    --config '/path/e2e/parallel with space/rector.php'
                 *
                 * tested in macOS and Ubuntu (github action)
                 */
                $config = $config_value;
                $worker_command_array[] = escapeshellarg($this->file_path_helper->relative_path($config));
            }
        }
        if ($input->get_option(Option::ONLY) !== null) {
            $worker_command_array[] = self::OPTION_DASHES . Option::ONLY;
            $worker_command_array[] = escapeshellarg((string) $input->get_option(Option::ONLY));
        }
        return implode(' ', $worker_command_array);
    }
    private function should_skip_option(Input_Interface $input, string $option_name): bool
    {
        if (!$input->has_option($option_name)) {
            return \true;
        }
        // skip output format and config, handled separately in create()
        return $option_name === Option::OUTPUT_FORMAT || $option_name === Option::CONFIG;
    }
    /**
     * @return string[]
     */
    private function get_command_option_names(Command $command): array
    {
        $input_definition = $command->get_definition();
        $option_names = [];
        foreach ($input_definition->get_options() as $input_option) {
            $option_names[] = $input_option->get_name();
        }
        return $option_names;
    }
    /**
     * Keeps all options that are allowed in check command options
     *
     * @param string[] $mainCommandOptionNames
     * @return string[]
     */
    private function mirror_command_options(Input_Interface $input, array $main_command_option_names): array
    {
        $worker_command_options = [];
        foreach ($main_command_option_names as $main_command_option_name) {
            if ($this->should_skip_option($input, $main_command_option_name)) {
                continue;
            }
            /** @var bool|string|null $optionValue */
            $option_value = $input->get_option($main_command_option_name);
            // skip clutter
            if ($option_value === null) {
                continue;
            }
            if (is_bool($option_value)) {
                if ($option_value) {
                    $worker_command_options[] = self::OPTION_DASHES . $main_command_option_name;
                }
                continue;
            }
            if ($main_command_option_name === 'memory-limit') {
                // symfony/console does not accept -1 as value without assign
                $worker_command_options[] = self::OPTION_DASHES . $main_command_option_name . '=' . \escapeshellarg($option_value);
            } else {
                $worker_command_options[] = self::OPTION_DASHES . $main_command_option_name;
                $worker_command_options[] = \escapeshellarg($option_value);
            }
        }
        return $worker_command_options;
    }
}