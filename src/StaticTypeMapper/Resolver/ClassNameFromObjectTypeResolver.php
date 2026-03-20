<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Resolver;

use Php_Stan\Type\Type;
final class Class_Name_From_Object_Type_Resolver
{
    public static function resolve(Type $type): ?string
    {
        $object_class_names = $type->get_object_class_names();
        if (count($object_class_names) !== 1) {
            return null;
        }
        return $object_class_names[0];
    }
}