<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Comparing;

use Php_Parser\Node;
use Rector\Comments\Comment_Remover;
use Rector\Php_Parser\Printer\Better_Standard_Printer;
final class Node_Comparator
{
    /**
     * @readonly
     */
    private Comment_Remover $comment_remover;
    /**
     * @readonly
     */
    private Better_Standard_Printer $better_standard_printer;
    public function __construct(Comment_Remover $comment_remover, Better_Standard_Printer $better_standard_printer)
    {
        $this->comment_remover = $comment_remover;
        $this->better_standard_printer = $better_standard_printer;
    }
    /**
     * Removes all comments from both nodes
     * @param Node|Node[]|null $node
     */
    public function print_without_comments($node): string
    {
        $node = $this->comment_remover->remove_from_node($node);
        $content = $this->better_standard_printer->print($node);
        return trim($content);
    }
    /**
     * @param Node|Node[]|null $firstNode
     * @param Node|Node[]|null $secondNode
     */
    public function are_nodes_equal($first_node, $second_node): bool
    {
        if ($first_node instanceof Node && !$second_node instanceof Node) {
            return \false;
        }
        if (!$first_node instanceof Node && $second_node instanceof Node) {
            return \false;
        }
        if (is_array($first_node) && !is_array($second_node)) {
            return \false;
        }
        if (!is_array($second_node)) {
            return $this->print_without_comments($first_node) === $this->print_without_comments($second_node);
        }
        if (is_array($first_node)) {
            return $this->print_without_comments($first_node) === $this->print_without_comments($second_node);
        }
        return \false;
    }
    /**
     * @api
     * @param Node[] $availableNodes
     */
    public function is_node_equal(Node $single_node, array $available_nodes): bool
    {
        foreach ($available_nodes as $available_node) {
            if ($this->are_nodes_equal($single_node, $available_node)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Checks even clone nodes
     */
    public function are_same_node(Node $first_node, Node $second_node): bool
    {
        if ($first_node === $second_node) {
            return \true;
        }
        $first_class = get_class($first_node);
        $second_class = get_class($second_node);
        if ($first_class !== $second_class) {
            return \false;
        }
        if ($first_node->get_start_token_pos() !== $second_node->get_start_token_pos()) {
            return \false;
        }
        if ($first_node->get_end_token_pos() !== $second_node->get_end_token_pos()) {
            return \false;
        }
        $print_first_node = $this->better_standard_printer->print($first_node);
        $print_second_node = $this->better_standard_printer->print($second_node);
        return $print_first_node === $print_second_node;
    }
}