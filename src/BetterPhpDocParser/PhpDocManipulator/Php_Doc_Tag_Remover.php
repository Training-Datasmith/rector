<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Manipulator;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
final class Php_Doc_Tag_Remover
{
    public function remove_by_name(Php_Doc_Info $php_doc_info, string $name): bool
    {
        $has_changed = \false;
        $php_doc_node = $php_doc_info->get_php_doc_node();
        foreach ($php_doc_node->children as $key => $php_doc_child_node) {
            if (!$php_doc_child_node instanceof Php_Doc_Tag_Node) {
                continue;
            }
            if ($this->are_annotation_names_equal($name, $php_doc_child_node->name)) {
                unset($php_doc_node->children[$key]);
                $has_changed = \true;
            }
            if ($php_doc_child_node->value instanceof Doctrine_Annotation_Tag_Value_Node && $php_doc_child_node->value->has_class_name($name)) {
                unset($php_doc_node->children[$key]);
                $has_changed = \true;
            }
        }
        return $has_changed;
    }
    public function remove_tag_value_from_node(Php_Doc_Info $php_doc_info, Node $desired_node): bool
    {
        $php_doc_node = $php_doc_info->get_php_doc_node();
        $has_changed = \false;
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $php_doc_node_traverser->traverse_with_callable($php_doc_node, '', static function (Node $node) use ($desired_node, &$has_changed): ?int {
            if ($node instanceof Php_Doc_Tag_Node && $node->value === $desired_node) {
                $has_changed = \true;
                return Php_Doc_Node_Traverser::NODE_REMOVE;
            }
            if ($node !== $desired_node) {
                return null;
            }
            $has_changed = \true;
            return Php_Doc_Node_Traverser::NODE_REMOVE;
        });
        return $has_changed;
    }
    private function are_annotation_names_equal(string $first_annotation_name, string $second_annotation_name): bool
    {
        return trim($first_annotation_name, '@') === trim($second_annotation_name, '@');
    }
}