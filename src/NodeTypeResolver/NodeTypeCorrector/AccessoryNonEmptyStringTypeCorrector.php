<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Corrector;

use Php_Stan\Type\Intersection_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
final class Accessory_Non_Empty_String_Type_Corrector
{
    public function correct(Type $main_type): Type
    {
        if (!$main_type instanceof Intersection_Type) {
            return $main_type;
        }
        if (!$main_type->is_non_empty_string()->yes()) {
            return $main_type;
        }
        return new String_Type();
    }
}