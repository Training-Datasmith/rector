<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Node_Finder;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
final class Php_Doc_Node_By_Type_Finder
{
    /**
     * @template TNode as \PHPStan\PhpDocParser\Ast\Node
     * @param class-string<TNode> $desiredType
     * @return array<TNode>
     */
    public function find_by_type(Php_Doc_Node $php_doc_node, string $desired_type): array
    {
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $found_nodes = [];
        $php_doc_node_traverser->traverse_with_callable($php_doc_node, '', static function (Node $node) use (&$found_nodes, $desired_type): Node {
            if (!$node instanceof $desired_type) {
                return $node;
            }
            /** @var TNode $node */
            $found_nodes[] = $node;
            return $node;
        });
        return $found_nodes;
    }
    /**
     * @param string[] $classes
     * @return DoctrineAnnotationTagValueNode[]
     */
    public function find_doctrine_annotations_by_classes(Php_Doc_Node $php_doc_node, array $classes): array
    {
        $doctrine_annotation_tag_value_nodes = [];
        foreach ($classes as $class) {
            $just_found_tag_value_nodes = $this->find_doctrine_annotations_by_class($php_doc_node, $class);
            $doctrine_annotation_tag_value_nodes = array_merge($doctrine_annotation_tag_value_nodes, $just_found_tag_value_nodes);
        }
        return $doctrine_annotation_tag_value_nodes;
    }
    /**
     * @param class-string $desiredClass
     * @return DoctrineAnnotationTagValueNode[]
     */
    public function find_doctrine_annotations_by_class(Php_Doc_Node $php_doc_node, string $desired_class): array
    {
        $desired_doctrine_tag_value_nodes = [];
        /** @var DoctrineAnnotationTagValueNode[] $doctrineTagValueNodes */
        $doctrine_tag_value_nodes = $this->find_by_type($php_doc_node, Doctrine_Annotation_Tag_Value_Node::class);
        foreach ($doctrine_tag_value_nodes as $doctrine_tag_value_node) {
            if ($doctrine_tag_value_node->has_class_name($desired_class)) {
                $desired_doctrine_tag_value_nodes[] = $doctrine_tag_value_node;
            }
        }
        return $desired_doctrine_tag_value_nodes;
    }
}