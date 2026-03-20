<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\String_;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
use Rector\Php_Attribute\Enum\Doc_Tag_Node_State;
use Rector\Php_Parser\Node\Value\Value_Resolver;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @implements AnnotationToAttributeMapperInterface<mixed[]>
 */
final class Array_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
{
    /**
     * @readonly
     */
    private Value_Resolver $value_resolver;
    private Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper;
    public function __construct(Value_Resolver $value_resolver)
    {
        $this->value_resolver = $value_resolver;
    }
    public function autowire(Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper): void
    {
        $this->annotation_to_attribute_mapper = $annotation_to_attribute_mapper;
    }
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool
    {
        return is_array($value);
    }
    /**
     * @param mixed[] $value
     */
    public function map($value): Array_
    {
        $array_items = [];
        foreach ($value as $key => $single_value) {
            $value_expr = $this->annotation_to_attribute_mapper->map($single_value);
            // remove node
            if ($value_expr === Doc_Tag_Node_State::REMOVE_ARRAY) {
                continue;
            }
            // remove value
            if ($this->is_remove_array_placeholder($single_value)) {
                continue;
            }
            if ($value_expr instanceof Array_Item) {
                $value_expr = $this->resolve_value_expr_with_single_quote_handling($value_expr);
                $array_items[] = $this->resolve_value_expr_with_single_quote_handling($value_expr);
            } else {
                $key_expr = null;
                if (!is_int($key)) {
                    $key_expr = $this->annotation_to_attribute_mapper->map($key);
                    Assert::is_instance_of($key_expr, Expr::class);
                }
                $array_items[] = new Array_Item($value_expr, $key_expr);
            }
        }
        return new Array_($array_items);
    }
    private function resolve_value_expr_with_single_quote_handling(Array_Item $array_item): Array_Item
    {
        if (!$array_item->key instanceof Expr && $array_item->value instanceof Class_Const_Fetch && $array_item->value->class instanceof Name && strpos((string) $array_item->value->class, "'") !== \false) {
            $array_item->value = new String_($this->value_resolver->get_value($array_item->value));
            return $array_item;
        }
        return $array_item;
    }
    /**
     * @param mixed $value
     */
    private function is_remove_array_placeholder($value): bool
    {
        if (!is_array($value)) {
            return \false;
        }
        return in_array(Doc_Tag_Node_State::REMOVE_ARRAY, $value, \true);
    }
}