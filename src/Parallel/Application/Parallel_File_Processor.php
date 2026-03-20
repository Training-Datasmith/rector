<?php

declare (strict_types=1);
namespace Rector\Parallel\Application;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Console\Command\Process_Command;
use Rector\Parallel\Command\Worker_Command_Line_Factory;
use Rector\Parallel\Value_Object\Bridge;
use Rector\Value_Object\Error\System_Error;
use Rector\Value_Object\Process_Result;
use Rector\Value_Object\Reporting\File_Diff;
use Rector_Prefix202603\Clue\React\Nd_Json\Decoder;
use Rector_Prefix202603\Clue\React\Nd_Json\Encoder;
use Rector_Prefix202603\Nette\Utils\Random;
use Rector_Prefix202603\React\Event_Loop\Stream_Select_Loop;
use Rector_Prefix202603\React\Socket\Connection_Interface;
use Rector_Prefix202603\React\Socket\Tcp_Server;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symplify\Easy_Parallel\Enum\Action;
use Rector_Prefix202603\Symplify\Easy_Parallel\Enum\Content;
use Rector_Prefix202603\Symplify\Easy_Parallel\Enum\React_Command;
use Rector_Prefix202603\Symplify\Easy_Parallel\Enum\React_Event;
use Rector_Prefix202603\Symplify\Easy_Parallel\Value_Object\Parallel_Process;
use Rector_Prefix202603\Symplify\Easy_Parallel\Value_Object\Process_Pool;
use Rector_Prefix202603\Symplify\Easy_Parallel\Value_Object\Schedule;
use Throwable;
/**
 * Inspired from @see
 * https://github.com/phpstan/phpstan-src/commit/9124c66dcc55a222e21b1717ba5f60771f7dda92#diff-39c7a3b0cbb217bbfff96fbb454e6e5e60c74cf92fbb0f9d246b8bebbaad2bb0
 *
 * https://github.com/phpstan/phpstan-src/commit/b84acd2e3eadf66189a64fdbc6dd18ff76323f67#diff-7f625777f1ce5384046df08abffd6c911cfbb1cfc8fcb2bdeaf78f337689e3e2R150
 */
final class Parallel_File_Processor
{
    /**
     * @readonly
     */
    private Worker_Command_Line_Factory $worker_command_line_factory;
    /**
     * @var int
     */
    private const SYSTEM_ERROR_LIMIT = 50;
    /**
     * The number of chunks a worker can process before getting killed.
     * In contrast the jobSize defines the maximum size of a chunk, a worker process at a time.
     * @var int
     */
    private const MAX_CHUNKS_PER_WORKER = 8;
    /**
     * @var \Symplify\EasyParallel\ValueObject\ProcessPool|null
     */
    private ?\Rector_Prefix202603\Symplify\Easy_Parallel\Value_Object\Process_Pool $process_pool = null;
    public function __construct(Worker_Command_Line_Factory $worker_command_line_factory)
    {
        $this->worker_command_line_factory = $worker_command_line_factory;
    }
    /**
     * @param callable(int $stepCount): void $postFileCallback Used for progress bar jump
     */
    public function process(Schedule $schedule, string $main_script, callable $post_file_callback, Input_Interface $input): Process_Result
    {
        $jobs = array_reverse($schedule->get_jobs());
        $stream_select_loop = new Stream_Select_Loop();
        // basic properties setup
        $number_of_processes = $schedule->get_number_of_processes();
        // initial counters
        /** @var FileDiff[] $fileDiffs */
        $file_diffs = [];
        /** @var SystemError[] $systemErrors */
        $system_errors = [];
        $tcp_server = new Tcp_Server('127.0.0.1:0', $stream_select_loop);
        $this->process_pool = new Process_Pool($tcp_server);
        $tcp_server->on(React_Event::CONNECTION, function (Connection_Interface $connection) use (&$jobs): void {
            $in_decoder = new Decoder($connection, \true, 512, 0, 4 * 1024 * 1024);
            $out_encoder = new Encoder($connection);
            $in_decoder->on(React_Event::DATA, function (array $data) use (&$jobs, $in_decoder, $out_encoder): void {
                $action = $data[React_Command::ACTION];
                if ($action !== Action::HELLO) {
                    return;
                }
                $process_identifier = $data[Option::PARALLEL_IDENTIFIER];
                $parallel_process = $this->process_pool->get_process($process_identifier);
                $parallel_process->bind_connection($in_decoder, $out_encoder);
                if ($jobs === []) {
                    $this->process_pool->quit_process($process_identifier);
                    return;
                }
                $jobs_chunk = array_pop($jobs);
                $parallel_process->request([React_Command::ACTION => Action::MAIN, Content::FILES => $jobs_chunk]);
            });
        });
        /** @var string $serverAddress */
        $server_address = $tcp_server->get_address();
        /** @var int $serverPort */
        $server_port = parse_url($server_address, \PHP_URL_PORT);
        $system_errors_count = 0;
        $reached_system_errors_count_limit = \false;
        $total_changed = 0;
        $handle_error_callable = function (Throwable $throwable) use (&$system_errors, &$system_errors_count, &$reached_system_errors_count_limit): void {
            $system_errors[] = new System_Error($throwable->get_message(), $throwable->get_file(), $throwable->get_line());
            ++$system_errors_count;
            $reached_system_errors_count_limit = \true;
            $this->process_pool->quit_all();
            // This sleep has to be here, because event though we have called $this->processPool->quitAll(),
            // it takes some time for the child processes to actually die, during which they can still write to cache
            // @see https://github.com/rectorphp/rector-src/pull/3834/files#r1231696531
            sleep(1);
        };
        $timeout_in_seconds = Simple_Parameter_Provider::provide_int_parameter(Option::PARALLEL_JOB_TIMEOUT_IN_SECONDS);
        $file_chunks_budget_per_process = [];
        $process_spawner = function () use (&$system_errors, &$file_diffs, &$jobs, $post_file_callback, &$system_errors_count, &$reached_internal_errors_count_limit, $main_script, $input, $server_port, $stream_select_loop, $timeout_in_seconds, $handle_error_callable, &$file_chunks_budget_per_process, &$process_spawner, &$total_changed): void {
            $process_identifier = Random::generate();
            $worker_command_line = $this->worker_command_line_factory->create($main_script, Process_Command::class, 'worker', $input, $process_identifier, $server_port);
            $file_chunks_budget_per_process[$process_identifier] = self::MAX_CHUNKS_PER_WORKER;
            $parallel_process = new Parallel_Process($worker_command_line, $stream_select_loop, $timeout_in_seconds);
            $parallel_process->start(
                // 1. callable on data
                function (array $json) use ($parallel_process, &$system_errors, &$file_diffs, &$jobs, $post_file_callback, &$system_errors_count, &$reached_internal_errors_count_limit, $process_identifier, &$file_chunks_budget_per_process, &$process_spawner, &$total_changed): void {
                    /** @var array{
                     *      total_changed: int,
                     *      system_errors: mixed[],
                     *      file_diffs: array<string, mixed>,
                     *      files_count: int,
                     *      system_errors_count: int
                     * } $json */
                    $total_changed += $json[Bridge::TOTAL_CHANGED];
                    // decode arrays to objects
                    foreach ($json[Bridge::SYSTEM_ERRORS] as $json_error) {
                        if (is_string($json_error)) {
                            $system_errors[] = new System_Error('System error: ' . $json_error);
                            continue;
                        }
                        $system_errors[] = System_Error::decode($json_error);
                    }
                    foreach ($json[Bridge::FILE_DIFFS] as $json_file_diff) {
                        $file_diffs[] = File_Diff::decode($json_file_diff);
                    }
                    $post_file_callback($json[Bridge::FILES_COUNT]);
                    $system_errors_count += $json[Bridge::SYSTEM_ERRORS_COUNT];
                    if ($system_errors_count >= self::SYSTEM_ERROR_LIMIT) {
                        $reached_internal_errors_count_limit = \true;
                        $this->process_pool->quit_all();
                    }
                    if ($file_chunks_budget_per_process[$process_identifier] <= 0) {
                        // kill the current worker, and spawn a fresh one to free memory
                        $this->process_pool->quit_process($process_identifier);
                        $process_spawner();
                        return;
                    }
                    if ($jobs === []) {
                        $this->process_pool->quit_process($process_identifier);
                        return;
                    }
                    $jobs_chunk = array_pop($jobs);
                    $parallel_process->request([React_Command::ACTION => Action::MAIN, Content::FILES => $jobs_chunk]);
                    --$file_chunks_budget_per_process[$process_identifier];
                },
                // 2. callable on error
                $handle_error_callable,
                // 3. callable on exit
                function ($exit_code, string $std_err) use (&$system_errors, $process_identifier): void {
                    $this->process_pool->try_quit_process($process_identifier);
                    if ($exit_code === Command::SUCCESS) {
                        return;
                    }
                    if ($exit_code === null) {
                        return;
                    }
                    $system_errors[] = new System_Error('Child process error: ' . $std_err);
                }
            );
            $this->process_pool->attach_process($process_identifier, $parallel_process);
        };
        for ($i = 0; $i < $number_of_processes; ++$i) {
            // nothing else to process, stop now
            if ($jobs === []) {
                break;
            }
            $process_spawner();
        }
        $stream_select_loop->run();
        if ($reached_system_errors_count_limit) {
            $system_errors[] = new System_Error(sprintf('Reached system errors count limit of %d, exiting...', self::SYSTEM_ERROR_LIMIT));
        }
        return new Process_Result($system_errors, $file_diffs, $total_changed);
    }
}