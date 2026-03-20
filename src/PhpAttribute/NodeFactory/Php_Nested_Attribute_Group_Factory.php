<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Node_Factory;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Stmt\Nop;
use Php_Parser\Node\Stmt\Use_;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Token_Iterator_Factory;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Doctrine_Annotation_Decorator;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation\Curly_List_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php80\Value_Object\Annotation_Property_To_Attribute_Class;
use Rector\Php80\Value_Object\Nested_Annotation_To_Attribute;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Attribute_Array_Name_Inliner;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Php_Nested_Attribute_Group_Factory
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
    private Attribute_Array_Name_Inliner $attribute_array_name_inliner;
    /**
     * @readonly
     */
    private Token_Iterator_Factory $token_iterator_factory;
    /**
     * @readonly
     */
    private Static_Doctrine_Annotation_Parser $static_doctrine_annotation_parser;
    public function __construct(Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper, \Rector\Php_Attribute\Node_Factory\Attribute_Name_Factory $attribute_name_factory, \Rector\Php_Attribute\Node_Factory\Named_Args_Factory $named_args_factory, Attribute_Array_Name_Inliner $attribute_array_name_inliner, Token_Iterator_Factory $token_iterator_factory, Static_Doctrine_Annotation_Parser $static_doctrine_annotation_parser)
    {
        $this->annotation_to_attribute_mapper = $annotation_to_attribute_mapper;
        $this->attribute_name_factory = $attribute_name_factory;
        $this->named_args_factory = $named_args_factory;
        $this->attribute_array_name_inliner = $attribute_array_name_inliner;
        $this->token_iterator_factory = $token_iterator_factory;
        $this->static_doctrine_annotation_parser = $static_doctrine_annotation_parser;
    }
    /**
     * @param Use_[] $uses
     */
    public function create(Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node, Nested_Annotation_To_Attribute $nested_annotation_to_attribute, array $uses): Attribute_Group
    {
        $values = $doctrine_annotation_tag_value_node->get_values();
        $values = $this->remove_items($values, $nested_annotation_to_attribute);
        $args = $this->create_args_from_items($values);
        $args = $this->attribute_array_name_inliner->inline_array_to_args($args);
        $attribute_name = $this->attribute_name_factory->create($nested_annotation_to_attribute, $doctrine_annotation_tag_value_node, $uses);
        $attribute = new Attribute($attribute_name, $args);
        return new Attribute_Group([$attribute]);
    }
    /**
     * @return AttributeGroup[]
     */
    public function create_nested(Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node, Nested_Annotation_To_Attribute $nested_annotation_to_attribute): array
    {
        $attribute_groups = [];
        if ($nested_annotation_to_attribute->has_explicit_parameters()) {
            return $this->create_from_explicit_properties($nested_annotation_to_attribute, $doctrine_annotation_tag_value_node);
        }
        $nested_annotation_property_to_attribute_class = $nested_annotation_to_attribute->get_annotation_properties_to_attribute_classes()[0];
        foreach ($doctrine_annotation_tag_value_node->values as $array_item_node) {
            $nested_doctrine_annotation_tag_value_node = $array_item_node->value;
            if (!$nested_doctrine_annotation_tag_value_node instanceof Curly_List_Node) {
                continue;
            }
            foreach ($nested_doctrine_annotation_tag_value_node->values as $nested_array_item_node) {
                if (!$nested_array_item_node->value instanceof Doctrine_Annotation_Tag_Value_Node) {
                    continue;
                }
                $attribute_args = $this->create_attribute_args($nested_array_item_node->value);
                $original_identifier = $doctrine_annotation_tag_value_node->identifier_type_node->name;
                $attribute_name = $this->resolve_aliased_attribute_name($original_identifier, $nested_annotation_property_to_attribute_class);
                $attribute = new Attribute($attribute_name, $attribute_args);
                $attribute_groups[] = new Attribute_Group([$attribute]);
            }
        }
        return $attribute_groups;
    }
    /**
     * @return list<Arg>
     */
    private function create_attribute_args(Doctrine_Annotation_Tag_Value_Node $nested_doctrine_annotation_tag_value_node): array
    {
        $args = $this->create_args_from_items($nested_doctrine_annotation_tag_value_node->get_values());
        return $this->attribute_array_name_inliner->inline_array_to_args($args);
    }
    /**
     * @param ArrayItemNode[] $arrayItemNodes
     * @return list<Arg>
     */
    private function create_args_from_items(array $array_item_nodes): array
    {
        $array_item_nodes = $this->annotation_to_attribute_mapper->map($array_item_nodes);
        $values = $array_item_nodes instanceof Array_ ? $array_item_nodes->items : $array_item_nodes;
        return $this->named_args_factory->create_from_values($values);
    }
    /**
     * @todo improve this hardcoded approach later
     * @return \PhpParser\Node\Name\FullyQualified|\PhpParser\Node\Name
     */
    private function resolve_aliased_attribute_name(string $original_identifier, Annotation_Property_To_Attribute_Class $annotation_property_to_attribute_class)
    {
        /** @var string $shortDoctrineAttributeName */
        $short_doctrine_attribute_name = Strings::after($annotation_property_to_attribute_class->get_attribute_class(), '\\', -1);
        if (strncmp($original_identifier, '@ORM', strlen('@ORM')) === 0) {
            // or alias
            return new Name('ORM\\' . $short_doctrine_attribute_name);
        }
        // short alias
        if (strpos($original_identifier, '\\') === \false) {
            return new Name($short_doctrine_attribute_name);
        }
        return new Fully_Qualified($annotation_property_to_attribute_class->get_attribute_class());
    }
    /**
     * @param ArrayItemNode[] $arrayItemNodes
     * @return ArrayItemNode[]
     */
    private function remove_items(array $array_item_nodes, Nested_Annotation_To_Attribute $nested_annotation_to_attribute): array
    {
        foreach ($nested_annotation_to_attribute->get_annotation_properties_to_attribute_classes() as $annotation_property_to_attribute_class) {
            foreach ($array_item_nodes as $key => $array_item_node) {
                if ($array_item_node->key !== $annotation_property_to_attribute_class->get_annotation_property()) {
                    continue;
                }
                unset($array_item_nodes[$key]);
            }
        }
        return $array_item_nodes;
    }
    /**
     * @return AttributeGroup[]
     */
    private function create_from_explicit_properties(Nested_Annotation_To_Attribute $nested_annotation_to_attribute, Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node): array
    {
        $attribute_groups = [];
        foreach ($nested_annotation_to_attribute->get_annotation_properties_to_attribute_classes() as $annotation_property_to_attribute_class) {
            /** @var string $annotationProperty */
            $annotation_property = $annotation_property_to_attribute_class->get_annotation_property();
            $nested_array_item_node = $doctrine_annotation_tag_value_node->get_value($annotation_property);
            if (!$nested_array_item_node instanceof Array_Item_Node) {
                continue;
            }
            if (!$nested_array_item_node->value instanceof Curly_List_Node) {
                throw new Should_Not_Happen_Exception();
            }
            foreach ($nested_array_item_node->value->get_values() as $array_item_node) {
                $nested_doctrine_annotation_tag_value_node = $array_item_node->value;
                if (!$nested_doctrine_annotation_tag_value_node instanceof Doctrine_Annotation_Tag_Value_Node) {
                    Assert::string($nested_doctrine_annotation_tag_value_node);
                    $match = Strings::match($nested_doctrine_annotation_tag_value_node, Doctrine_Annotation_Decorator::LONG_ANNOTATION_REGEX);
                    if (!isset($match['class_name'])) {
                        throw new Should_Not_Happen_Exception();
                    }
                    $identifier_type_node = new Identifier_Type_Node($match['class_name']);
                    $identifier_type_node->set_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS, $match['class_name']);
                    $annotation_content = $match['annotation_content'] ?? '';
                    $nested_token_iterator = $this->token_iterator_factory->create($annotation_content);
                    // mimics doctrine behavior just in phpdoc-parser syntax :)
                    // https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L742
                    $values = $this->static_doctrine_annotation_parser->resolve_annotation_method_call($nested_token_iterator, new Nop());
                    $nested_doctrine_annotation_tag_value_node = new Doctrine_Annotation_Tag_Value_Node($identifier_type_node, $match['annotation_content'] ?? '', $values);
                }
                $attribute_args = $this->create_attribute_args($nested_doctrine_annotation_tag_value_node);
                $original_identifier = $nested_doctrine_annotation_tag_value_node->identifier_type_node->name;
                $attribute_name = $this->resolve_aliased_attribute_name($original_identifier, $annotation_property_to_attribute_class);
                if ($annotation_property_to_attribute_class->does_need_new_import() && count($attribute_name->get_parts()) === 1) {
                    $attribute_name->set_attribute(Attribute_Key::EXTRA_USE_IMPORT, $annotation_property_to_attribute_class->get_attribute_class());
                }
                $attribute = new Attribute($attribute_name, $attribute_args);
                $attribute_groups[] = new Attribute_Group([$attribute]);
            }
        }
        return $attribute_groups;
    }
}