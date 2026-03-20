<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Comment;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Inline_Html;
use Php_Parser\Node\Stmt\Nop;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Comparing\Node_Comparator;
final class Comments_Merger
{
    /**
     * @readonly
     */
    private Node_Comparator $node_comparator;
    public function __construct(Node_Comparator $node_comparator)
    {
        $this->node_comparator = $node_comparator;
    }
    public function mirror_comments(Node $new_node, Node $old_node): void
    {
        if ($old_node instanceof Inline_Html) {
            return;
        }
        if ($this->node_comparator->are_same_node($new_node, $old_node)) {
            return;
        }
        $old_php_doc_info = $old_node->get_attribute(Attribute_Key::PHP_DOC_INFO);
        $new_php_doc_info = $new_node->get_attribute(Attribute_Key::PHP_DOC_INFO);
        if ($new_php_doc_info instanceof Php_Doc_Info) {
            if (!$old_php_doc_info instanceof Php_Doc_Info) {
                return;
            }
            if ((string) $old_php_doc_info->get_php_doc_node() !== (string) $new_php_doc_info->get_php_doc_node()) {
                return;
            }
        }
        $new_node->set_attribute(Attribute_Key::PHP_DOC_INFO, $old_php_doc_info);
        if (!$new_node instanceof Nop) {
            $new_node->set_attribute(Attribute_Key::COMMENTS, $old_node->get_attribute(Attribute_Key::COMMENTS));
        }
    }
    /**
     * @param Node[] $mergedNodes
     */
    public function keep_comments(Node $new_node, array $merged_nodes): void
    {
        $comments = $new_node->get_comments();
        foreach ($merged_nodes as $merged_node) {
            $comments = array_merge($comments, $merged_node->get_comments());
        }
        if ($comments === []) {
            return;
        }
        $new_node->set_attribute(Attribute_Key::COMMENTS, $comments);
        // remove so comments "win"
        $new_node->set_attribute(Attribute_Key::PHP_DOC_INFO, null);
    }
}