<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan\Type;

use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Constant_Scalar_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Union_Type;
final class Static_Type_Analyzer
{
    public function is_always_truable_type(Type $type): bool
    {
        if ($type instanceof Mixed_Type) {
            return \false;
        }
        if ($type instanceof Constant_Array_Type) {
            return \true;
        }
        if ($type instanceof Array_Type) {
            return $this->is_always_truable_array_type($type);
        }
        if ($type instanceof Union_Type && Type_Combinator::contains_null($type)) {
            return \false;
        }
        // always trueish
        if ($type instanceof Object_Type) {
            return \true;
        }
        if ($type instanceof Constant_Scalar_Type && !$type->is_null()->yes()) {
            return (bool) $type->get_value();
        }
        if ($type->is_scalar()->yes()) {
            return \false;
        }
        return $this->is_always_truable_union_type($type);
    }
    private function is_always_truable_union_type(Type $type): bool
    {
        if (!$type instanceof Union_Type) {
            return \false;
        }
        foreach ($type->get_types() as $unioned_type) {
            if (!$this->is_always_truable_type($unioned_type)) {
                return \false;
            }
        }
        return \true;
    }
    private function is_always_truable_array_type(Array_Type $array_type): bool
    {
        $item_type = $array_type->get_iterable_value_type();
        if (!$item_type instanceof Constant_Scalar_Type) {
            return \false;
        }
        return (bool) $item_type->get_value();
    }
}