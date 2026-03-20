<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Parser;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node_Traverser;
use Php_Parser\Parser;
use Php_Parser\Parser_Factory;
use Rector\Php_Parser\Node_Visitor\Assigned_To_Node_Visitor;
use Rector_Prefix202603\Nette\Utils\File_System;
use Throwable;
final class Simple_Php_Parser
{
    /**
     * @readonly
     */
    private Parser $php_parser;
    /**
     * @readonly
     */
    private Node_Traverser $node_traverser;
    public function __construct()
    {
        $parser_factory = new Parser_Factory();
        $this->php_parser = $parser_factory->create_for_newest_supported_version();
        $this->node_traverser = new Node_Traverser(new Assigned_To_Node_Visitor());
    }
    /**
     * @api tests
     * @return Node[]
     */
    public function parse_file(string $file_path): array
    {
        $file_content = File_System::read($file_path);
        return $this->parse_string($file_content);
    }
    /**
     * @return Node[]
     */
    public function parse_string(string $file_content): array
    {
        $file_content = $this->ensure_file_contents_has_opening_tag($file_content);
        $has_added_semicolon = \false;
        try {
            $nodes = $this->php_parser->parse($file_content);
        } catch (Throwable $exception) {
            // try adding missing closing semicolon ;
            $file_content .= ';';
            $has_added_semicolon = \true;
            $nodes = $this->php_parser->parse($file_content);
        }
        if ($nodes === null) {
            return [];
        }
        $nodes = $this->restore_expression_pre_wrap($nodes, $has_added_semicolon);
        return $this->node_traverser->traverse($nodes);
    }
    private function ensure_file_contents_has_opening_tag(string $file_content): string
    {
        if (strncmp(trim($file_content), '<?php', strlen('<?php')) !== 0) {
            // prepend with PHP opening tag to make parse PHP code
            return '<?php ' . $file_content;
        }
        return $file_content;
    }
    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    private function restore_expression_pre_wrap(array $nodes, bool $has_added_semicolon): array
    {
        if (!$has_added_semicolon) {
            return $nodes;
        }
        if (count($nodes) !== 1) {
            return $nodes;
        }
        // remove added semicolon to be honest about Expression
        $only_stmt = $nodes[0];
        if (!$only_stmt instanceof Expression) {
            return $nodes;
        }
        return [$only_stmt->expr];
    }
}