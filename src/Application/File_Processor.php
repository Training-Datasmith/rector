<?php

declare (strict_types=1);
namespace Rector\Application;

use Php_Stan\Analysed_Code_Exception;
use Php_Stan\Parser\Parser_Errors_Exception;
use Rector\Caching\Detector\Changed_Files_Detector;
use Rector\Changes_Reporting\Value_Object_Factory\Error_Factory;
use Rector\Changes_Reporting\Value_Object_Factory\File_Diff_Factory;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\File_System\File_Path_Helper;
use Rector\Node_Type_Resolver\Node_Scope_And_Metadata_Decorator;
use Rector\Php_Parser\Node\File_Node;
use Rector\Php_Parser\Node_Traverser\Rector_Node_Traverser;
use Rector\Php_Parser\Parser\Parser_Errors;
use Rector\Php_Parser\Parser\Rector_Parser;
use Rector\Php_Parser\Printer\Better_Standard_Printer;
use Rector\Post_Rector\Application\Post_File_Processor;
use Rector\Testing\Php_Unit\Static_Php_Unit_Environment;
use Rector\Value_Object\Application\File;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Error\System_Error;
use Rector\Value_Object\File_Process_Result;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use Throwable;
final class File_Processor
{
    /**
     * @readonly
     */
    private Better_Standard_Printer $better_standard_printer;
    /**
     * @readonly
     */
    private Rector_Node_Traverser $rector_node_traverser;
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @readonly
     */
    private File_Diff_Factory $file_diff_factory;
    /**
     * @readonly
     */
    private Changed_Files_Detector $changed_files_detector;
    /**
     * @readonly
     */
    private Error_Factory $error_factory;
    /**
     * @readonly
     */
    private File_Path_Helper $file_path_helper;
    /**
     * @readonly
     */
    private Post_File_Processor $post_file_processor;
    /**
     * @readonly
     */
    private Rector_Parser $rector_parser;
    /**
     * @readonly
     */
    private Node_Scope_And_Metadata_Decorator $node_scope_and_metadata_decorator;
    public function __construct(Better_Standard_Printer $better_standard_printer, Rector_Node_Traverser $rector_node_traverser, Symfony_Style $symfony_style, File_Diff_Factory $file_diff_factory, Changed_Files_Detector $changed_files_detector, Error_Factory $error_factory, File_Path_Helper $file_path_helper, Post_File_Processor $post_file_processor, Rector_Parser $rector_parser, Node_Scope_And_Metadata_Decorator $node_scope_and_metadata_decorator)
    {
        $this->better_standard_printer = $better_standard_printer;
        $this->rector_node_traverser = $rector_node_traverser;
        $this->symfony_style = $symfony_style;
        $this->file_diff_factory = $file_diff_factory;
        $this->changed_files_detector = $changed_files_detector;
        $this->error_factory = $error_factory;
        $this->file_path_helper = $file_path_helper;
        $this->post_file_processor = $post_file_processor;
        $this->rector_parser = $rector_parser;
        $this->node_scope_and_metadata_decorator = $node_scope_and_metadata_decorator;
    }
    public function process_file(File $file, Configuration $configuration): File_Process_Result
    {
        // 1. parse files to nodes
        $parsing_system_error = $this->parse_file_and_decorate_nodes($file);
        if ($parsing_system_error instanceof System_Error) {
            // we cannot process this file as the parsing and type resolving itself went wrong
            return new File_Process_Result([$parsing_system_error], null, \false);
        }
        $file_has_changed = \false;
        $file_path = $file->get_file_path();
        do {
            $file->change_has_changed(\false);
            // 1. change nodes with Rector Rules
            $new_stmts = $this->rector_node_traverser->traverse($file->get_new_stmts());
            // 2. apply post rectors
            $post_new_stmts = $this->post_file_processor->traverse($new_stmts, $file);
            // 3. this is needed for new tokens added in "afterTraverse()"
            $file->change_new_stmts($post_new_stmts);
            // 4. print to file or string
            // important to detect if file has changed
            $this->print_file($file, $configuration, $file_path);
            // no change in current iteration, stop
            if (!$file->has_changed()) {
                break;
            }
            $file_has_changed = \true;
        } while (\true);
        // 5. add as cacheable if not changed at all
        if (!$file_has_changed) {
            $this->changed_files_detector->add_cacheable_file($file_path);
        } else {
            // when changed, set final status changed to true
            // to ensure it make sense to verify in next process when needed
            $file->change_has_changed(\true);
        }
        $rector_with_line_changes = $file->get_rector_with_line_changes();
        if ($file->has_changed() || $rector_with_line_changes !== []) {
            $current_file_diff = $this->file_diff_factory->create_file_diff_with_line_changes($configuration->should_show_diffs(), $file, $file->get_original_file_content(), $file->get_file_content(), $file->get_rector_with_line_changes());
            $file->set_file_diff($current_file_diff);
        }
        return new File_Process_Result([], $file->get_file_diff(), $file->has_changed());
    }
    private function parse_file_and_decorate_nodes(File $file): ?System_Error
    {
        try {
            try {
                $this->parse_file_nodes($file);
            } catch (Parser_Errors_Exception $exception) {
                $this->parse_file_nodes($file, \false);
            }
        } catch (Should_Not_Happen_Exception $should_not_happen_exception) {
            throw $should_not_happen_exception;
        } catch (Analysed_Code_Exception $analysed_code_exception) {
            // inform about missing classes in tests
            if (Static_Php_Unit_Environment::is_php_unit_run()) {
                throw $analysed_code_exception;
            }
            return $this->error_factory->create_autoload_error($analysed_code_exception, $file->get_file_path());
        } catch (Throwable $throwable) {
            if ($this->symfony_style->is_verbose() || Static_Php_Unit_Environment::is_php_unit_run()) {
                throw $throwable;
            }
            $relative_file_path = $this->file_path_helper->relative_path($file->get_file_path());
            if ($throwable instanceof Parser_Errors_Exception) {
                $throwable = new Parser_Errors($throwable);
            }
            return new System_Error($throwable->get_message(), $relative_file_path, $throwable->get_line());
        }
        return null;
    }
    private function print_file(File $file, Configuration $configuration, string $file_path): void
    {
        // only save to string first, no need to print to file when not needed
        $new_file_content = $this->better_standard_printer->print_format_preserving($file->get_new_stmts(), $file->get_old_stmts(), $file->get_old_tokens());
        // change file content early to make $file->hasChanged() based on new content
        $file->change_file_content($new_file_content);
        if ($configuration->is_dry_run()) {
            return;
        }
        if (!$file->has_changed()) {
            return;
        }
        File_System::write($file_path, $new_file_content, null);
    }
    private function parse_file_nodes(File $file, bool $for_newest_supported_version = \true): void
    {
        // store tokens by original file content, so we don't have to print them right now
        $stmts_and_tokens = $this->rector_parser->parse_file_content_to_stmts_and_tokens($file->get_original_file_content(), $for_newest_supported_version);
        $old_stmts = $stmts_and_tokens->get_stmts();
        // wrap in FileNode to allow file-level rules
        $old_stmts = [new File_Node($old_stmts)];
        $old_tokens = $stmts_and_tokens->get_tokens();
        $new_stmts = $this->node_scope_and_metadata_decorator->decorate_nodes_from_file($file->get_file_path(), $old_stmts);
        $file->hydrate_stmts_and_tokens($new_stmts, $old_stmts, $old_tokens);
    }
}