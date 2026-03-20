<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Corrector;

use Php_Stan\Type\Accessory\Non_Empty_Array_Type;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Intersection_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
final class Accessory_Non_Empty_Array_Type_Corrector
{
    public function correct(Type $main_type): Type
    {
        if (!$main_type instanceof Intersection_Type) {
            return $main_type;
        }
        if (!$main_type->is_array()->yes()) {
            return $main_type;
        }
        foreach ($main_type->get_types() as $type) {
            if ($type instanceof Non_Empty_Array_Type) {
                return new Array_Type(new Mixed_Type(), new Mixed_Type());
            }
            if ($type instanceof Array_Type && $type->get_iterable_value_type() instanceof Intersection_Type && $type->get_iterable_value_type()->is_string()->yes()) {
                return new Array_Type(new Mixed_Type(), new String_Type());
            }
        }
        return $main_type;
    }
}