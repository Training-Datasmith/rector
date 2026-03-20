<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Type;

use Override;
use Php_Stan\Php_Doc_Parser\Ast\Type\Array_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Callable_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Generic_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Union_Type_Node;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Array_Type_Mapper;
final class Spacing_Aware_Array_Type_Node extends Array_Type_Node
{
    #[Override]
    public function __toString(): string
    {
        if ($this->type instanceof Callable_Type_Node) {
            return sprintf('(%s)[]', (string) $this->type);
        }
        $type_as_string = (string) $this->type;
        if ($this->is_generic_array_candidate($this->type)) {
            return sprintf('array<%s>', $type_as_string);
        }
        if ($this->type instanceof Array_Type_Node) {
            return $this->print_array_type($this->type);
        }
        if ($this->type instanceof \Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Union_Type_Node) {
            return $this->print_union_type($this->type);
        }
        return $type_as_string . '[]';
    }
    private function is_generic_array_candidate(Type_Node $type_node): bool
    {
        $has_generic_type_parent = (bool) $this->get_attribute(Array_Type_Mapper::HAS_GENERIC_TYPE_PARENT);
        if (!$has_generic_type_parent) {
            return \false;
        }
        return $type_node instanceof Union_Type_Node || $type_node instanceof Array_Type_Node;
    }
    private function print_array_type(Array_Type_Node $array_type_node): string
    {
        $type_as_string = (string) $array_type_node;
        $single_types_as_string = explode('|', $type_as_string);
        foreach ($single_types_as_string as $key => $single_type_as_string) {
            $single_types_as_string[$key] = $single_type_as_string . '[]';
        }
        return implode('|', $single_types_as_string);
    }
    private function print_union_type(\Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Union_Type_Node $brackets_aware_union_type_node): string
    {
        if ($brackets_aware_union_type_node->is_wrapped_in_brackets()) {
            return $brackets_aware_union_type_node . '[]';
        }
        // If all types in the union are GenericTypeNode, use array<union> syntax
        $all_generic = \true;
        $first_generic_type_name = null;
        foreach ($brackets_aware_union_type_node->types as $unioned_type) {
            if (!$unioned_type instanceof Generic_Type_Node) {
                $all_generic = \false;
                break;
            }
            // ensure only check on base level
            // avoid mix usage without [] added
            if (count($unioned_type->generic_types) !== 1) {
                $all_generic = \false;
                break;
            }
            // ensure all generic types has the same base type
            $current_type_name = $unioned_type->type->name;
            if ($first_generic_type_name === null) {
                $first_generic_type_name = $current_type_name;
            } elseif ($first_generic_type_name !== $current_type_name) {
                // Different generic base types (e.g., class-string vs array)
                $all_generic = \false;
                break;
            }
        }
        if ($all_generic) {
            return sprintf('array<int, %s>', (string) $brackets_aware_union_type_node);
        }
        $unioned_types = [];
        foreach ($brackets_aware_union_type_node->types as $unioned_type) {
            $unioned_types[] = $unioned_type . '[]';
        }
        return implode('|', $unioned_types);
    }
}