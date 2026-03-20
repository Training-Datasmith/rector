<?php

declare (strict_types=1);
namespace Rector\Console\Command;

use Rector\Application\Application_File_Processor;
use Rector\Autoloading\Additional_Autoloader;
use Rector\Configuration\Configuration_Factory;
use Rector\Configuration\Configuration_Rule_Filter;
use Rector\Console\Process_Configure_Decorator;
use Rector\Parallel\Value_Object\Bridge;
use Rector\Static_Reflection\Dynamic_Source_Locator_Decorator;
use Rector\Util\Memory_Limiter;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Error\System_Error;
use Rector_Prefix202603\Clue\React\Nd_Json\Decoder;
use Rector_Prefix202603\Clue\React\Nd_Json\Encoder;
use Rector_Prefix202603\React\Event_Loop\Stream_Select_Loop;
use Rector_Prefix202603\React\Socket\Connection_Interface;
use Rector_Prefix202603\React\Socket\Tcp_Connector;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
use Rector_Prefix202603\Symplify\Easy_Parallel\Enum\Action;
use Rector_Prefix202603\Symplify\Easy_Parallel\Enum\React_Command;
use Rector_Prefix202603\Symplify\Easy_Parallel\Enum\React_Event;
use Rector_Prefix202603\Webmozart\Assert\Assert;
use Throwable;
/**
 * Inspired at: https://github.com/phpstan/phpstan-src/commit/9124c66dcc55a222e21b1717ba5f60771f7dda92
 * https://github.com/phpstan/phpstan-src/blob/c471c7b050e0929daf432288770de673b394a983/src/Command/WorkerCommand.php
 *
 * ↓↓↓
 * https://github.com/phpstan/phpstan-src/commit/b84acd2e3eadf66189a64fdbc6dd18ff76323f67#diff-7f625777f1ce5384046df08abffd6c911cfbb1cfc8fcb2bdeaf78f337689e3e2
 */
final class Worker_Command extends Command
{
    /**
     * @readonly
     */
    private Additional_Autoloader $additional_autoloader;
    /**
     * @readonly
     */
    private Dynamic_Source_Locator_Decorator $dynamic_source_locator_decorator;
    /**
     * @readonly
     */
    private Application_File_Processor $application_file_processor;
    /**
     * @readonly
     */
    private Memory_Limiter $memory_limiter;
    /**
     * @readonly
     */
    private Configuration_Factory $configuration_factory;
    /**
     * @readonly
     */
    private Configuration_Rule_Filter $configuration_rule_filter;
    /**
     * @var string
     */
    private const RESULT = 'result';
    public function __construct(Additional_Autoloader $additional_autoloader, Dynamic_Source_Locator_Decorator $dynamic_source_locator_decorator, Application_File_Processor $application_file_processor, Memory_Limiter $memory_limiter, Configuration_Factory $configuration_factory, Configuration_Rule_Filter $configuration_rule_filter)
    {
        $this->additional_autoloader = $additional_autoloader;
        $this->dynamic_source_locator_decorator = $dynamic_source_locator_decorator;
        $this->application_file_processor = $application_file_processor;
        $this->memory_limiter = $memory_limiter;
        $this->configuration_factory = $configuration_factory;
        $this->configuration_rule_filter = $configuration_rule_filter;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_name('worker');
        $this->set_description('[INTERNAL] Support for parallel process');
        Process_Configure_Decorator::decorate($this);
        parent::configure();
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $configuration = $this->configuration_factory->create_from_input($input);
        $this->memory_limiter->adjust($configuration);
        $this->configuration_rule_filter->set_configuration($configuration);
        $stream_select_loop = new Stream_Select_Loop();
        $parallel_identifier = $configuration->get_parallel_identifier();
        $tcp_connector = new Tcp_Connector($stream_select_loop);
        $promise = $tcp_connector->connect('127.0.0.1:' . $configuration->get_parallel_port());
        $promise->then(function (Connection_Interface $connection) use ($parallel_identifier, $configuration, $output): void {
            $in_decoder = new Decoder($connection, \true, 512, \JSON_INVALID_UTF8_IGNORE);
            $out_encoder = new Encoder($connection, \JSON_INVALID_UTF8_IGNORE);
            $out_encoder->write([React_Command::ACTION => Action::HELLO, React_Command::IDENTIFIER => $parallel_identifier]);
            $this->run_worker($out_encoder, $in_decoder, $configuration, $output);
        });
        $stream_select_loop->run();
        return self::SUCCESS;
    }
    private function run_worker(Encoder $encoder, Decoder $decoder, Configuration $configuration, Output_Interface $output): void
    {
        $this->additional_autoloader->autoload_paths();
        $this->dynamic_source_locator_decorator->add_paths($configuration->get_paths());
        if ($configuration->is_debug()) {
            $pre_file_callback = static function (string $file_path) use ($output): void {
                $output->writeln($file_path);
            };
        } else {
            $pre_file_callback = null;
        }
        // 1. handle system error
        $handle_error_callback = static function (Throwable $throwable) use ($encoder): void {
            $system_error = new System_Error($throwable->get_message(), $throwable->get_file(), $throwable->get_line());
            $encoder->write([React_Command::ACTION => Action::RESULT, self::RESULT => [Bridge::SYSTEM_ERRORS => [$system_error], Bridge::FILES_COUNT => 0, Bridge::SYSTEM_ERRORS_COUNT => 1]]);
            $encoder->end();
        };
        $encoder->on(React_Event::ERROR, $handle_error_callback);
        // 2. collect diffs + errors from file processor
        $decoder->on(React_Event::DATA, function (array $json) use ($pre_file_callback, $encoder, $configuration): void {
            $action = $json[React_Command::ACTION];
            if ($action !== Action::MAIN) {
                return;
            }
            /** @var string[] $filePaths */
            $file_paths = $json[Bridge::FILES] ?? [];
            Assert::not_empty($file_paths);
            $process_result = $this->application_file_processor->process_files($file_paths, $configuration, $pre_file_callback);
            /**
             * this invokes all listeners listening $decoder->on(...) @see \Symplify\EasyParallel\Enum\ReactEvent::DATA
             */
            $encoder->write([React_Command::ACTION => Action::RESULT, self::RESULT => [Bridge::FILE_DIFFS => $process_result->get_file_diffs(), Bridge::FILES_COUNT => count($file_paths), Bridge::SYSTEM_ERRORS => $process_result->get_system_errors(), Bridge::SYSTEM_ERRORS_COUNT => count($process_result->get_system_errors()), Bridge::TOTAL_CHANGED => $process_result->get_total_changed()]]);
        });
        $decoder->on(React_Event::ERROR, $handle_error_callback);
    }
}