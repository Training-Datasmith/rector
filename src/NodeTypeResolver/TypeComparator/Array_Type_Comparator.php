<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Type_Comparator;

use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Mixed_Type;
/**
 * @see \Rector\Tests\NodeTypeResolver\TypeComparator\ArrayTypeComparatorTest
 */
final class Array_Type_Comparator
{
    /**
     * @param \PHPStan\Type\ArrayType|\PHPStan\Type\Constant\ConstantArrayType $checkedType
     * @param \PHPStan\Type\ArrayType|\PHPStan\Type\Constant\ConstantArrayType $mainType
     */
    public function is_subtype($checked_type, $main_type): bool
    {
        if (!$checked_type instanceof Constant_Array_Type && !$main_type instanceof Constant_Array_Type) {
            return $main_type->is_super_type_of($checked_type)->yes();
        }
        $checked_key_type = $checked_type->get_iterable_key_type();
        $main_key_type = $main_type->get_iterable_key_type();
        if (!$main_key_type instanceof Mixed_Type && $main_key_type->is_super_type_of($checked_key_type)->yes()) {
            return \true;
        }
        $checked_item_type = $checked_type->get_iterable_value_type();
        $main_item_type = $main_type->get_iterable_value_type();
        return $checked_item_type->is_super_type_of($main_item_type)->yes();
    }
}