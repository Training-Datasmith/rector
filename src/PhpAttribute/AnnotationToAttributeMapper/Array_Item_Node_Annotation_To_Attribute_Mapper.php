<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
use Rector\Php_Attribute\Enum\Doc_Tag_Node_State;
use Rector\Validation\Rector_Assert;
use Rector_Prefix202603\Webmozart\Assert\InvalidArgumentException;
/**
 * @implements AnnotationToAttributeMapperInterface<ArrayItemNode>
 */
final class Array_Item_Node_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
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
        return $value instanceof Array_Item_Node;
    }
    /**
     * @param ArrayItemNode $arrayItemNode
     */
    public function map($array_item_node): Array_Item
    {
        $value_expr = $this->annotation_to_attribute_mapper->map($array_item_node->value);
        if ($value_expr === Doc_Tag_Node_State::REMOVE_ARRAY) {
            return new Array_Item(new String_($value_expr));
        }
        if ($array_item_node->key !== null) {
            /** @var Expr $keyExpr */
            $key_expr = $this->annotation_to_attribute_mapper->map($array_item_node->key);
        } else {
            if ($this->has_no_parentheses_annotation($array_item_node)) {
                try {
                    Rector_Assert::class_name(ltrim((string) $array_item_node->value, '@'));
                    $identifier_type_node = new Identifier_Type_Node($array_item_node->value);
                    $array_item_node->value = new Doctrine_Annotation_Tag_Value_Node($identifier_type_node);
                    return $this->map($array_item_node);
                } catch (InvalidArgumentException $exception) {
                }
            }
            $key_expr = null;
        }
        // @todo how to skip natural integer keys?
        return new Array_Item($value_expr, $key_expr);
    }
    private function has_no_parentheses_annotation(Array_Item_Node $array_item_node): bool
    {
        if ($array_item_node->value instanceof String_Node) {
            return \false;
        }
        if (!is_string($array_item_node->value)) {
            return \false;
        }
        if (strncmp($array_item_node->value, '@', strlen('@')) !== 0) {
            return \false;
        }
        return substr_compare($array_item_node->value, ')', -strlen(')')) !== 0;
    }
}