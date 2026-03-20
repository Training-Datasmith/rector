<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan\Type;

use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Constant\Constant_Float_Type;
use Php_Stan\Type\Constant\Constant_Integer_Type;
use Php_Stan\Type\Constant\Constant_String_Type;
use Php_Stan\Type\Float_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Utils;
use Php_Stan\Type\Union_Type;
use Rector\Node_Type_Resolver\Php_Stan\Object_Without_Class_Type_With_Parent_Types;
use Rector\Node_Type_Resolver\Php_Stan\Type_Hasher;
final class Type_Factory
{
    /**
     * @readonly
     */
    private Type_Hasher $type_hasher;
    public function __construct(Type_Hasher $type_hasher)
    {
        $this->type_hasher = $type_hasher;
    }
    /**
     * @param Type[] $types
     */
    public function create_mixed_passed_or_union_type_and_keep_constant(array $types): Type
    {
        $types = $this->unwrap_unioned_types($types);
        $types = $this->uniquate_types($types, \true);
        return $this->create_union_or_single_type($types);
    }
    /**
     * @param Type[] $types
     */
    public function create_mixed_passed_or_union_type(array $types, bool $keep_constant_types = \false): Type
    {
        $types = $this->unwrap_unioned_types($types);
        $types = $this->uniquate_types($types, $keep_constant_types);
        return $this->create_union_or_single_type($types);
    }
    /**
     * @template TType as Type
     * @param array<TType> $types
     * @return array<TType>
     */
    public function uniquate_types(array $types, bool $keep_constant = \false): array
    {
        $constant_type_hashes = [];
        $unique_types = [];
        $total_types = count($types);
        $has_false = \false;
        $has_true = \false;
        foreach ($types as $type) {
            $type = $this->normalize_object_type($total_types, $type);
            $type = $this->normalize_boolean_type($has_false, $has_true, $type);
            $removed_constant_type = $this->remove_value_from_constant_type($type);
            $removed_constant_type_hash = $this->type_hasher->create_type_hash($removed_constant_type);
            if ($keep_constant && $type !== $removed_constant_type) {
                $type_hash = $this->type_hasher->create_type_hash($type);
                $constant_type_hashes[$type_hash] = $removed_constant_type_hash;
            } else {
                $type = $removed_constant_type;
                $type_hash = $removed_constant_type_hash;
            }
            $unique_types[$type_hash] = $type;
        }
        foreach ($constant_type_hashes as $constant_type_hash => $removed_constant_type_hash) {
            if (array_key_exists($removed_constant_type_hash, $unique_types)) {
                unset($unique_types[$constant_type_hash]);
            }
        }
        // re-index
        return array_values($unique_types);
    }
    private function normalize_object_type(int $total_types, Type $type): Type
    {
        if ($total_types > 1 && $type instanceof Object_Without_Class_Type_With_Parent_Types) {
            $parents = $type->get_parent_types();
            return new Object_Type($parents[0]->get_class_name());
        }
        return $type;
    }
    private function normalize_boolean_type(bool &$has_false, bool &$has_true, Type $type): Type
    {
        if ($type->is_true()->yes()) {
            $has_true = \true;
        }
        if ($type->is_false()->yes()) {
            $has_false = \true;
        }
        if ($has_false && $has_true && ($type->is_true()->yes() || $type->is_false()->yes())) {
            return new Boolean_Type();
        }
        return $type;
    }
    /**
     * @param Type[] $types
     * @return Type[]
     */
    private function unwrap_unioned_types(array $types): array
    {
        // unwrap union types
        $unwrapped_types = [];
        foreach ($types as $type) {
            $flatten_types = Type_Utils::flatten_types($type);
            foreach ($flatten_types as $flatten_type) {
                if ($flatten_type instanceof Constant_Array_Type) {
                    $unwrapped_types = array_merge($unwrapped_types, $this->unwrap_constant_array_types($flatten_type));
                } else {
                    $unwrapped_types = $this->resolve_non_constant_array_type($flatten_type, $unwrapped_types);
                }
            }
        }
        return $unwrapped_types;
    }
    /**
     * @param Type[] $unwrappedTypes
     * @return Type[]
     */
    private function resolve_non_constant_array_type(Type $type, array $unwrapped_types): array
    {
        $unwrapped_types[] = $type;
        return $unwrapped_types;
    }
    /**
     * @param Type[] $types
     */
    private function create_union_or_single_type(array $types): Type
    {
        if ($types === []) {
            return new Mixed_Type();
        }
        if (count($types) === 1) {
            return $types[0];
        }
        foreach ($types as $type) {
            if ($type instanceof Mixed_Type) {
                return new Mixed_Type();
            }
        }
        return new Union_Type($types);
    }
    private function remove_value_from_constant_type(Type $type): Type
    {
        // remove values from constant types
        if ($type instanceof Constant_Float_Type) {
            return new Float_Type();
        }
        if ($type instanceof Constant_String_Type) {
            return new String_Type();
        }
        if ($type instanceof Constant_Integer_Type) {
            return new Integer_Type();
        }
        if ($type->is_true()->yes() || $type->is_false()->yes()) {
            return new Boolean_Type();
        }
        return $type;
    }
    /**
     * @return Type[]
     */
    private function unwrap_constant_array_types(Constant_Array_Type $constant_array_type): array
    {
        $unwrapped_types = [];
        $flatten_key_types = Type_Utils::flatten_types($constant_array_type->get_iterable_key_type());
        $flatten_item_types = Type_Utils::flatten_types($constant_array_type->get_iterable_value_type());
        foreach ($flatten_item_types as $position => $nested_flatten_item_type) {
            $nested_flatten_key_type = $flatten_key_types[$position] ?? null;
            if (!$nested_flatten_key_type instanceof Type) {
                $nested_flatten_key_type = new Mixed_Type();
            }
            if ($nested_flatten_item_type instanceof Constant_Array_Type) {
                $inner_array_types = $this->unwrap_constant_array_types($nested_flatten_item_type);
                foreach ($inner_array_types as $inner_array_type) {
                    // preserve outer array -> inner array structure: array<outerKey, innerArray>
                    $unwrapped_types[] = new Array_Type($nested_flatten_key_type, $inner_array_type);
                }
                continue;
            }
            $unwrapped_types[] = new Array_Type($nested_flatten_key_type, $nested_flatten_item_type);
        }
        return $unwrapped_types;
    }
}