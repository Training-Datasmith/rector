<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Printer;

use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Child_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Better_Php_Doc_Parser\Value_Object\Start_And_End;
final class Remove_Nodes_Start_And_End_Resolver
{
    /**
     * @param array<int, mixed> $tokens
     * @return StartAndEnd[]
     */
    public function resolve(Php_Doc_Node $original_php_doc_node, Php_Doc_Node $current_php_doc_node, array $tokens): array
    {
        $removed_node_positions = [];
        /** @var PhpDocChildNode[] $removedChildNodes */
        $removed_child_nodes = array_diff($original_php_doc_node->children, $current_php_doc_node->children);
        $last_end_position = null;
        foreach ($removed_child_nodes as $removed_child_node) {
            /** @var StartAndEnd|null $removedPhpDocNodeInfo */
            $removed_php_doc_node_info = $removed_child_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
            // it's not there when comment block has empty row "\s\*\n"
            if (!$removed_php_doc_node_info instanceof Start_And_End) {
                continue;
            }
            // change start position to start of the line, so the whole line is removed
            $seek_position = $removed_php_doc_node_info->get_start();
            while ($seek_position >= 0 && $tokens[$seek_position][1] !== Lexer::TOKEN_HORIZONTAL_WS) {
                if ($tokens[$seek_position][1] === Lexer::TOKEN_PHPDOC_EOL) {
                    break;
                }
                // do not collide
                if ($last_end_position < $seek_position) {
                    break;
                }
                --$seek_position;
            }
            $last_end_position = $removed_php_doc_node_info->get_end();
            $removed_node_positions[] = new Start_And_End(max(0, $seek_position - 1), $removed_php_doc_node_info->get_end());
        }
        return $removed_node_positions;
    }
}