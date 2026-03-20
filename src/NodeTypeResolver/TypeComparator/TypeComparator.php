<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Type_Comparator;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Constant_Scalar_Type;
use Php_Stan\Type\Generic\Template_Object_Type;
use Php_Stan\Type\Generic\Template_Type;
use Php_Stan\Type\Intersection_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Static_Type;
use Php_Stan\Type\This_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Traverser;
use Php_Stan\Type\Union_Type;
use Rector\Node_Type_Resolver\Php_Stan\Type_Hasher;
use Rector\Reflection\Reflection_Resolver;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
final class Type_Comparator
{
    /**
     * @readonly
     */
    private Type_Hasher $type_hasher;
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private \Rector\Node_Type_Resolver\Type_Comparator\Array_Type_Comparator $array_type_comparator;
    /**
     * @readonly
     */
    private \Rector\Node_Type_Resolver\Type_Comparator\Scalar_Type_Comparator $scalar_type_comparator;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    public function __construct(Type_Hasher $type_hasher, Static_Type_Mapper $static_type_mapper, \Rector\Node_Type_Resolver\Type_Comparator\Array_Type_Comparator $array_type_comparator, \Rector\Node_Type_Resolver\Type_Comparator\Scalar_Type_Comparator $scalar_type_comparator, Reflection_Resolver $reflection_resolver)
    {
        $this->type_hasher = $type_hasher;
        $this->static_type_mapper = $static_type_mapper;
        $this->array_type_comparator = $array_type_comparator;
        $this->scalar_type_comparator = $scalar_type_comparator;
        $this->reflection_resolver = $reflection_resolver;
    }
    public function are_types_equal(Type $first_type, Type $second_type): bool
    {
        $first_type = $this->normalize_template_type($first_type);
        $second_type = $this->normalize_template_type($second_type);
        $first_type_hash = $this->type_hasher->create_type_hash($first_type);
        $second_type_hash = $this->type_hasher->create_type_hash($second_type);
        if ($first_type_hash === $second_type_hash) {
            return \true;
        }
        if ($this->scalar_type_comparator->are_equal_scalar($first_type, $second_type)) {
            return \true;
        }
        // aliases and types
        if ($this->are_aliased_object_matching_fqn_object($first_type, $second_type)) {
            return \true;
        }
        if ($this->type_hasher->are_types_equal($first_type, $second_type)) {
            return \true;
        }
        // is template of
        return $this->are_array_type_with_single_object_child_to_parent($first_type, $second_type);
    }
    public function are_php_parser_and_php_stan_php_doc_types_equal(Node $php_parser_node, Type_Node $php_stan_doc_type_node, Node $node): bool
    {
        $php_parser_node_type = $this->static_type_mapper->map_php_parser_node_php_stan_type($php_parser_node);
        $php_stan_doc_type = $this->static_type_mapper->map_php_stan_php_doc_type_node_to_php_stan_type($php_stan_doc_type_node, $node);
        if (!$this->are_types_equal($php_parser_node_type, $php_stan_doc_type) && $this->is_subtype($php_stan_doc_type, $php_parser_node_type)) {
            return \false;
        }
        // normalize bool union types
        $php_parser_node_type = $this->normalize_constant_boolean_type($php_parser_node_type);
        $php_stan_doc_type = $this->normalize_constant_boolean_type($php_stan_doc_type);
        // is scalar replace by another - remove it?
        $are_different_scalar_types = $this->scalar_type_comparator->are_different_scalar_types($php_parser_node_type, $php_stan_doc_type);
        if (!$are_different_scalar_types && !$this->are_types_equal($php_parser_node_type, $php_stan_doc_type)) {
            return \false;
        }
        if ($this->are_types_same_with_literal_type_in_php_doc($are_different_scalar_types, $php_stan_doc_type, $php_parser_node_type)) {
            return \false;
        }
        if ($php_stan_doc_type instanceof Union_Type || $php_stan_doc_type instanceof Intersection_Type) {
            foreach ($php_stan_doc_type->get_types() as $type) {
                if ($type instanceof Template_Object_Type) {
                    return \false;
                }
            }
        }
        return $this->is_this_type_in_final_class($php_stan_doc_type, $php_parser_node_type, $php_parser_node);
    }
    public function is_subtype(Type $checked_type, Type $main_type): bool
    {
        $checked_type = $this->normalize_template_type($checked_type);
        $main_type = $this->normalize_template_type($main_type);
        if ($main_type instanceof Mixed_Type) {
            return \false;
        }
        if (!$main_type instanceof Array_Type) {
            return $main_type->is_super_type_of($checked_type)->yes();
        }
        if (!$checked_type instanceof Array_Type) {
            return $main_type->is_super_type_of($checked_type)->yes();
        }
        return $this->array_type_comparator->is_subtype($checked_type, $main_type);
    }
    /**
     * unless it by ref, object param has its own life vs redefined variable
     * see https://3v4l.org/dI5Pe vs https://3v4l.org/S8i71
     */
    private function normalize_template_type(Type $type): Type
    {
        return $type instanceof Template_Type ? $type->get_bound() : $type;
    }
    private function are_aliased_object_matching_fqn_object(Type $first_type, Type $second_type): bool
    {
        if ($first_type instanceof Aliased_Object_Type && $second_type instanceof Object_Type) {
            return $first_type->get_fully_qualified_name() === $second_type->get_class_name();
        }
        if (!$first_type instanceof Object_Type) {
            return \false;
        }
        if (!$second_type instanceof Aliased_Object_Type) {
            return \false;
        }
        return $second_type->get_fully_qualified_name() === $first_type->get_class_name();
    }
    /**
     * E.g. class A extends B, class B → A[] is subtype of B[] → keep A[]
     */
    private function are_array_type_with_single_object_child_to_parent(Type $first_type, Type $second_type): bool
    {
        if (!$first_type instanceof Array_Type) {
            return \false;
        }
        if (!$second_type instanceof Array_Type) {
            return \false;
        }
        $first_array_item_type = $first_type->get_iterable_value_type();
        $second_array_item_type = $second_type->get_iterable_value_type();
        return $this->is_mutual_object_subtypes($first_array_item_type, $second_array_item_type);
    }
    private function is_mutual_object_subtypes(Type $first_array_item_type, Type $second_array_item_type): bool
    {
        if (!$first_array_item_type instanceof Object_Type) {
            return \false;
        }
        if (!$second_array_item_type instanceof Object_Type) {
            return \false;
        }
        if ($first_array_item_type->is_super_type_of($second_array_item_type)->yes()) {
            return \true;
        }
        return $second_array_item_type->is_super_type_of($first_array_item_type)->yes();
    }
    private function normalize_constant_boolean_type(Type $type): Type
    {
        return Type_Traverser::map($type, static function (Type $type, callable $callable): Type {
            if ($type->is_true()->yes() || $type->is_false()->yes()) {
                return new Boolean_Type();
            }
            return $callable($type);
        });
    }
    private function are_types_same_with_literal_type_in_php_doc(bool $are_different_scalar_types, Type $php_stan_doc_type, Type $php_parser_node_type): bool
    {
        return $are_different_scalar_types && $php_stan_doc_type instanceof Constant_Scalar_Type && $php_parser_node_type->is_super_type_of($php_stan_doc_type)->yes();
    }
    private function is_this_type_in_final_class(Type $php_stan_doc_type, Type $php_parser_node_type, Node $node): bool
    {
        /**
         * Special case for $this/(self|static) compare
         *
         * $this refers to the exact object identity, not just the same type. Therefore, it's valid and should not be removed
         * @see https://wiki.php.net/rfc/this_return_type for more context
         */
        if ($php_stan_doc_type instanceof This_Type && $php_parser_node_type instanceof Static_Type) {
            return \false;
        }
        $is_static_return_doc_type_with_this_type = $php_stan_doc_type instanceof Static_Type && $php_parser_node_type instanceof This_Type;
        if (!$is_static_return_doc_type_with_this_type) {
            return \true;
        }
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($node);
        if (!$class_reflection instanceof Class_Reflection || !$class_reflection->is_class()) {
            return \false;
        }
        return $class_reflection->is_final_by_keyword();
    }
}