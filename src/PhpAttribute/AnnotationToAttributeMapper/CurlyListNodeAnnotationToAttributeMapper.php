<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Scalar\Int_;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation\Curly_List_Node;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
use Rector\Php_Attribute\Enum\Doc_Tag_Node_State;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @implements AnnotationToAttributeMapperInterface<CurlyListNode>
 */
final class Curly_List_Node_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
{
    private Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper;
    /**
     * Avoid circular reference
     */
    public function autowire(Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper): void
    {
        $this->annotation_to_attribute_mapper = $annotation_to_attribute_mapper;
    }
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool
    {
        return $value instanceof Curly_List_Node;
    }
    /**
     * @param CurlyListNode $value
     */
    public function map($value): Array_
    {
        $array_items = [];
        $array_item_nodes = $value->get_values();
        $loop = -1;
        foreach ($array_item_nodes as $array_item_node) {
            $value_expr = $this->annotation_to_attribute_mapper->map($array_item_node);
            // remove node
            if ($value_expr === Doc_Tag_Node_State::REMOVE_ARRAY) {
                continue;
            }
            Assert::is_instance_of($value_expr, Array_Item::class);
            if (!is_numeric($array_item_node->key)) {
                $array_items[] = $value_expr;
                continue;
            }
            ++$loop;
            $array_item_node_key = (int) $array_item_node->key;
            if ($loop === $array_item_node_key) {
                $array_items[] = $value_expr;
                continue;
            }
            $value_expr->key = new Int_($array_item_node_key);
            $array_items[] = $value_expr;
        }
        return new Array_($array_items);
    }
}