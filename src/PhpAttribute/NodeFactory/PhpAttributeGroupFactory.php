<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Node_Factory;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Use_;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php80\Value_Object\Annotation_To_Attribute;
use Rector\Php81\Enum\Attribute_Name;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Attribute_Array_Name_Inliner;
/**
 * @see \Rector\Tests\PhpAttribute\Printer\PhpAttributeGroupFactoryTest
 */
final class Php_Attribute_Group_Factory
{
    /**
     * @readonly
     */
    private Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper;
    /**
     * @readonly
     */
    private \Rector\Php_Attribute\Node_Factory\Attribute_Name_Factory $attribute_name_factory;
    /**
     * @readonly
     */
    private \Rector\Php_Attribute\Node_Factory\Named_Args_Factory $named_args_factory;
    /**
     * @readonly
     */
    private \Rector\Php_Attribute\Node_Factory\Annotation_To_Attribute_Integer_Value_Caster $annotation_to_attribute_integer_value_caster;
    /**
     * @readonly
     */
    private Attribute_Array_Name_Inliner $attribute_array_name_inliner;
    public function __construct(Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper, \Rector\Php_Attribute\Node_Factory\Attribute_Name_Factory $attribute_name_factory, \Rector\Php_Attribute\Node_Factory\Named_Args_Factory $named_args_factory, \Rector\Php_Attribute\Node_Factory\Annotation_To_Attribute_Integer_Value_Caster $annotation_to_attribute_integer_value_caster, Attribute_Array_Name_Inliner $attribute_array_name_inliner)
    {
        $this->annotation_to_attribute_mapper = $annotation_to_attribute_mapper;
        $this->attribute_name_factory = $attribute_name_factory;
        $this->named_args_factory = $named_args_factory;
        $this->annotation_to_attribute_integer_value_caster = $annotation_to_attribute_integer_value_caster;
        $this->attribute_array_name_inliner = $attribute_array_name_inliner;
    }
    public function create_from_simple_tag(Annotation_To_Attribute $annotation_to_attribute, ?string $value = null): Attribute_Group
    {
        return $this->create_from_class($annotation_to_attribute->get_attribute_class(), $value);
    }
    /**
     * @param AttributeName::*|string $attributeClass
     */
    public function create_from_class(string $attribute_class, ?string $value = null): Attribute_Group
    {
        $fully_qualified = new Fully_Qualified($attribute_class);
        $attribute = new Attribute($fully_qualified);
        if ($value !== null && $value !== '') {
            $arg = new Arg(new String_($value));
            $attribute->args = [$arg];
        }
        return new Attribute_Group([$attribute]);
    }
    /**
     * @api tests
     * @param mixed[] $items
     */
    public function create_from_class_with_items(string $attribute_class, array $items): Attribute_Group
    {
        $fully_qualified = new Fully_Qualified($attribute_class);
        $args = $this->create_args_from_items($items);
        $attribute = new Attribute($fully_qualified, $args);
        return new Attribute_Group([$attribute]);
    }
    /**
     * @param Use_[] $uses
     */
    public function create(Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node, Annotation_To_Attribute $annotation_to_attribute, array $uses): Attribute_Group
    {
        $values = $doctrine_annotation_tag_value_node->get_values_with_silent_key();
        $args = $this->create_args_from_items($values, '', $annotation_to_attribute->get_class_reference_fields());
        $this->annotation_to_attribute_integer_value_caster->cast_attribute_types($annotation_to_attribute, $args);
        $args = $this->attribute_array_name_inliner->inline_array_to_args($args, $annotation_to_attribute->get_attribute_class());
        $attribute_name = $this->attribute_name_factory->create($annotation_to_attribute, $doctrine_annotation_tag_value_node, $uses);
        // keep FQN in the attribute, so it can be easily detected later
        $attribute_name->set_attribute(Attribute_Key::PHP_ATTRIBUTE_NAME, $annotation_to_attribute->get_attribute_class());
        $attribute = new Attribute($attribute_name, $args);
        return new Attribute_Group([$attribute]);
    }
    /**
     * @api tests
     *
     * @param ArrayItemNode[]|mixed[] $items
     * @param string $attributeClass @deprecated
     * @param string[] $classReferencedFields
     *
     * @return list<Arg>
     */
    public function create_args_from_items(array $items, string $attribute_class = '', array $class_referenced_fields = []): array
    {
        $mapped_items = $this->annotation_to_attribute_mapper->map($items);
        $this->map_class_references($mapped_items, $class_referenced_fields);
        $values = $mapped_items instanceof Array_ ? $mapped_items->items : $mapped_items;
        // the key here should contain the named argument
        return $this->named_args_factory->create_from_values($values);
    }
    /**
     * @param string[] $classReferencedFields
     * @param \PhpParser\Node\Expr|string $expr
     */
    private function map_class_references($expr, array $class_referenced_fields): void
    {
        if (!$expr instanceof Array_) {
            return;
        }
        foreach ($expr->items as $array_item) {
            if (!$array_item instanceof Array_Item) {
                continue;
            }
            if (!$array_item->key instanceof String_) {
                continue;
            }
            if (!in_array($array_item->key->value, $class_referenced_fields)) {
                continue;
            }
            if ($array_item->value instanceof Class_Const_Fetch) {
                continue;
            }
            if (!$array_item->value instanceof String_) {
                continue;
            }
            if ($array_item->value->value === '') {
                continue;
            }
            $array_item->value = new Class_Const_Fetch(new Fully_Qualified($array_item->value->value), 'class');
        }
    }
}