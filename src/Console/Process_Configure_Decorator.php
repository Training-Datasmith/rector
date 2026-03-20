<?php

declare (strict_types=1);
namespace Rector\Console;

use Rector\Changes_Reporting\Output\Console_Output_Formatter;
use Rector\Configuration\Option;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Argument;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Option;
final class Process_Configure_Decorator
{
    public static function decorate(Command $command): void
    {
        $command->add_argument(Option::SOURCE, Input_Argument::OPTIONAL | Input_Argument::IS_ARRAY, 'Files or directories to be upgraded.');
        $command->add_option(Option::DRY_RUN, Option::DRY_RUN_SHORT, Input_Option::VALUE_NONE, 'Only see the diff of changes, do not save them to files.');
        $command->add_option(Option::AUTOLOAD_FILE, Option::AUTOLOAD_FILE_SHORT, Input_Option::VALUE_REQUIRED, 'Path to file with extra autoload (will be included)');
        $command->add_option(Option::NO_PROGRESS_BAR, null, Input_Option::VALUE_NONE, 'Hide progress bar. Useful e.g. for nicer CI output.');
        $command->add_option(Option::NO_DIFFS, null, Input_Option::VALUE_NONE, 'Hide diffs of changed files. Useful e.g. for nicer CI output.');
        $command->add_option(Option::OUTPUT_FORMAT, null, Input_Option::VALUE_REQUIRED, 'Select output format', Console_Output_Formatter::NAME);
        // filter by rule and path
        $command->add_option(Option::ONLY, null, Input_Option::VALUE_REQUIRED, 'Fully qualified rule class name');
        $command->add_option(Option::ONLY_SUFFIX, null, Input_Option::VALUE_REQUIRED, 'Filter only files with specific suffix in name, e.g. "Controller"');
        $command->add_option(Option::DEBUG, null, Input_Option::VALUE_NONE, 'Display debug output.');
        $command->add_option(Option::MEMORY_LIMIT, null, Input_Option::VALUE_REQUIRED, 'Memory limit for process');
        $command->add_option(Option::CLEAR_CACHE, null, Input_Option::VALUE_NONE, 'Clear unchanged files cache');
        $command->add_option(Option::PARALLEL_PORT, null, Input_Option::VALUE_REQUIRED);
        $command->add_option(Option::PARALLEL_IDENTIFIER, null, Input_Option::VALUE_REQUIRED);
        $command->add_option(Option::XDEBUG, null, Input_Option::VALUE_NONE, 'Display xdebug output.');
    }
}