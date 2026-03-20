<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Mapper;

use Php_Stan\Type\Accessory\Accessory_Array_List_Type;
use Php_Stan\Type\Accessory\Accessory_Non_Empty_String_Type;
use Php_Stan\Type\Accessory\Non_Empty_Array_Type;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Callable_Type;
use Php_Stan\Type\Class_String_Type;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Float_Type;
use Php_Stan\Type\Integer_Range_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Iterable_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Never_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\Resource_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Void_Type;
use Rector_Prefix202603\Nette\Utils\Strings;
final class Scalar_String_To_Type_Mapper
{
    /**
     * @var array<class-string<Type>, string[]>
     */
    private const SCALAR_NAME_BY_TYPE = [String_Type::class => ['string'], Accessory_Non_Empty_String_Type::class => ['non-empty-string'], Non_Empty_Array_Type::class => ['non-empty-array'], Class_String_Type::class => ['class-string'], Float_Type::class => ['float', 'real', 'double'], Integer_Type::class => ['int', 'integer'], Boolean_Type::class => ['bool', 'boolean'], Null_Type::class => ['null'], Void_Type::class => ['void'], Resource_Type::class => ['resource'], Callable_Type::class => ['callback', 'callable'], Object_Without_Class_Type::class => ['object'], Never_Type::class => ['never', 'never-return', 'never-returns', 'no-return']];
    public function map_scalar_string_to_type(string $scalar_name): Type
    {
        $lowered_scalar_name = Strings::lower($scalar_name);
        if ($lowered_scalar_name === 'false') {
            return new Constant_Boolean_Type(\false);
        }
        if ($lowered_scalar_name === 'true') {
            return new Constant_Boolean_Type(\true);
        }
        if ($lowered_scalar_name === 'positive-int') {
            return Integer_Range_Type::create_all_greater_than(0);
        }
        if ($lowered_scalar_name === 'negative-int') {
            return Integer_Range_Type::create_all_smaller_than(0);
        }
        foreach (self::SCALAR_NAME_BY_TYPE as $object_type => $scalar_names) {
            if (!in_array($lowered_scalar_name, $scalar_names, \true)) {
                continue;
            }
            return new $object_type();
        }
        if ($lowered_scalar_name === 'list') {
            return Type_Combinator::intersect(new Array_Type(new Mixed_Type(), new Mixed_Type()), new Accessory_Array_List_Type());
        }
        if ($lowered_scalar_name === 'array') {
            return new Array_Type(new Mixed_Type(), new Mixed_Type());
        }
        if ($lowered_scalar_name === 'iterable') {
            return new Iterable_Type(new Mixed_Type(), new Mixed_Type());
        }
        if ($lowered_scalar_name === 'mixed') {
            return new Mixed_Type(\true);
        }
        return new Mixed_Type();
    }
}