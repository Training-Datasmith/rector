<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Type_Comparator;

use Php_Stan\Type\Type;
/**
 * @see \Rector\Tests\NodeTypeResolver\TypeComparator\ScalarTypeComparatorTest
 */
final class Scalar_Type_Comparator
{
    public function are_equal_scalar(Type $first_type, Type $second_type): bool
    {
        if ($first_type->is_string()->yes() && $second_type->is_string()->yes()) {
            // prevents "class-string" vs "string"
            $first_type_class = get_class($first_type);
            $second_type_class = get_class($second_type);
            return $first_type_class === $second_type_class;
        }
        if ($first_type->is_integer()->yes() && $second_type->is_integer()->yes()) {
            // prevents "int<min, max>" vs "int"
            $first_type_class = get_class($first_type);
            $second_type_class = get_class($second_type);
            return $first_type_class === $second_type_class;
        }
        if ($first_type->is_float()->yes() && $second_type->is_float()->yes()) {
            return \true;
        }
        if (!$first_type->is_boolean()->yes()) {
            return \false;
        }
        return $second_type->is_boolean()->yes();
    }
    /**
     * E.g. first is string, second is bool
     */
    public function are_different_scalar_types(Type $first_type, Type $second_type): bool
    {
        if (!$first_type->is_scalar()->yes()) {
            return \false;
        }
        if (!$second_type->is_scalar()->yes()) {
            return \false;
        }
        // treat class-string and string the same
        if ($first_type->is_string()->yes() && $second_type->is_string()->yes()) {
            return \false;
        }
        if ($first_type->is_integer()->yes() && $second_type->is_integer()->yes()) {
            return \false;
        }
        if (!$first_type->is_string()->yes()) {
            return get_class($first_type) !== get_class($second_type);
        }
        if (!$second_type->is_class_string()->yes()) {
            return get_class($first_type) !== get_class($second_type);
        }
        return \false;
    }
}