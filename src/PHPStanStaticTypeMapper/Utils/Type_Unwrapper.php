<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Utils;

use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Union_Type;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
final class Type_Unwrapper
{
    public function unwrap_first_object_type_from_union_type(Type $type): Type
    {
        if (!$type instanceof Union_Type) {
            return $type;
        }
        foreach ($type->get_types() as $unioned_type) {
            $class_name = Class_Name_From_Object_Type_Resolver::resolve($unioned_type);
            if ($class_name === null) {
                continue;
            }
            return $unioned_type;
        }
        return $type;
    }
    public function unwrap_first_callable_type_from_union_type(Type $type): Type
    {
        if (!$type instanceof Union_Type) {
            return $type;
        }
        foreach ($type->get_types() as $unioned_type) {
            if (!$unioned_type->is_callable()->yes()) {
                continue;
            }
            return $unioned_type;
        }
        return $type;
    }
    public function is_iterable_type_value(string $class_name, Type $type): bool
    {
        $type_class_name = Class_Name_From_Object_Type_Resolver::resolve($type);
        if ($type_class_name === null) {
            return \false;
        }
        // get the namespace from $className
        $class_namespace = $this->namespace($class_name);
        // get the namespace from $parameterReflection
        $reflection_namespace = $this->namespace($type_class_name);
        // then match with
        return $reflection_namespace === $class_namespace && substr_compare($type_class_name, 'TValue', -strlen('TValue')) === 0;
    }
    public function is_iterable_type_key(string $class_name, Type $type): bool
    {
        $type_class_name = Class_Name_From_Object_Type_Resolver::resolve($type);
        if ($type_class_name === null) {
            return \false;
        }
        // get the namespace from $className
        $class_namespace = $this->namespace($class_name);
        // get the namespace from $parameterReflection
        $reflection_namespace = $this->namespace($type_class_name);
        // then match with
        return $reflection_namespace === $class_namespace && substr_compare($type_class_name, 'TKey', -strlen('TKey')) === 0;
    }
    public function remove_null_type_from_union_type(Union_Type $union_type): Type
    {
        return Type_Combinator::remove_null($union_type);
    }
    private function namespace(string $class): string
    {
        return implode('\\', array_slice(explode('\\', $class), 0, -1));
    }
}