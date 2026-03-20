<?php

declare (strict_types=1);
namespace Rector\Testing\Testing_Parser;

use Php_Parser\Node;
use Rector\Application\Provider\Current_File_Provider;
use Rector\Node_Type_Resolver\Node_Scope_And_Metadata_Decorator;
use Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator_Provider\Dynamic_Source_Locator_Provider;
use Rector\Php_Parser\Node\File_Node;
use Rector\Php_Parser\Parser\Rector_Parser;
use Rector\Value_Object\Application\File;
use Rector_Prefix202603\Nette\Utils\File_System;
/**
 * @api
 */
final class Testing_Parser
{
    /**
     * @readonly
     */
    private Rector_Parser $rector_parser;
    /**
     * @readonly
     */
    private Node_Scope_And_Metadata_Decorator $node_scope_and_metadata_decorator;
    /**
     * @readonly
     */
    private Current_File_Provider $current_file_provider;
    /**
     * @readonly
     */
    private Dynamic_Source_Locator_Provider $dynamic_source_locator_provider;
    public function __construct(Rector_Parser $rector_parser, Node_Scope_And_Metadata_Decorator $node_scope_and_metadata_decorator, Current_File_Provider $current_file_provider, Dynamic_Source_Locator_Provider $dynamic_source_locator_provider)
    {
        $this->rector_parser = $rector_parser;
        $this->node_scope_and_metadata_decorator = $node_scope_and_metadata_decorator;
        $this->current_file_provider = $current_file_provider;
        $this->dynamic_source_locator_provider = $dynamic_source_locator_provider;
    }
    public function parse_file_path_to_file(string $file_path): File
    {
        [$file, $stmts] = $this->parse_to_file_and_stmts($file_path);
        return $file;
    }
    /**
     * @return Node[]
     */
    public function parse_file_to_decorated_nodes(string $file_path): array
    {
        [$file, $stmts] = $this->parse_to_file_and_stmts($file_path);
        return $stmts;
    }
    /**
     * @return array{0: File, 1: Node[]}
     */
    private function parse_to_file_and_stmts(string $file_path): array
    {
        // needed for PHPStan reflection, as it caches the last processed file
        $this->dynamic_source_locator_provider->set_file_path($file_path);
        $file_content = File_System::read($file_path);
        $file = new File($file_path, $file_content);
        $stmts = $this->rector_parser->parse_string($file_content);
        // wrap in FileNode to enable file-level rules
        $stmts = [new File_Node($stmts)];
        $stmts = $this->node_scope_and_metadata_decorator->decorate_nodes_from_file($file_path, $stmts);
        $file->hydrate_stmts_and_tokens($stmts, $stmts, []);
        $this->current_file_provider->set_file($file);
        return [$file, $stmts];
    }
}