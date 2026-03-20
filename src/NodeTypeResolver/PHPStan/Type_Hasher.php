<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan;

use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Generic\Generic_Object_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Traverser;
use Php_Stan\Type\Type_With_Class_Name;
use Php_Stan\Type\Verbosity_Level;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type;
final class Type_Hasher
{
    public function are_types_equal(Type $first_type, Type $second_type): bool
    {
        return $this->create_type_hash($first_type) === $this->create_type_hash($second_type);
    }
    public function create_type_hash(Type $type): string
    {
        if ($type instanceof Mixed_Type) {
            return $type->describe(Verbosity_Level::precise()) . $type->is_explicit_mixed();
        }
        if ($type instanceof Array_Type) {
            return $this->create_type_hash($type->get_iterable_value_type()) . $this->create_type_hash($type->get_iterable_key_type()) . $type->get_item_type()->describe(Verbosity_Level::precise()) . '[]';
        }
        if ($type instanceof Generic_Object_Type) {
            return $type->describe(Verbosity_Level::precise());
        }
        if ($type instanceof Type_With_Class_Name) {
            return $this->resolve_unique_type_with_class_name_hash($type);
        }
        if ($type->is_constant_value()->yes()) {
            return get_class($type);
        }
        $type = $this->normalize_object_type($type);
        return $type->describe(Verbosity_Level::value());
    }
    private function resolve_unique_type_with_class_name_hash(Type_With_Class_Name $type_with_class_name): string
    {
        if ($type_with_class_name instanceof Shortened_Object_Type) {
            return $type_with_class_name->get_fully_qualified_name();
        }
        if ($type_with_class_name instanceof Aliased_Object_Type) {
            return $type_with_class_name->get_fully_qualified_name();
        }
        return $type_with_class_name->get_class_name();
    }
    private function normalize_object_type(Type $type): Type
    {
        return Type_Traverser::map($type, static function (Type $current_type, callable $traverse_callback): Type {
            if ($current_type instanceof Shortened_Object_Type) {
                return new Fully_Qualified_Object_Type($current_type->get_fully_qualified_name());
            }
            if ($current_type instanceof Aliased_Object_Type) {
                return new Fully_Qualified_Object_Type($current_type->get_fully_qualified_name());
            }
            if ($current_type instanceof Object_Type && !$current_type instanceof Generic_Object_Type && $current_type->get_class_name() !== 'Iterator' && $current_type->get_class_name() !== 'iterable') {
                return new Fully_Qualified_Object_Type($current_type->get_class_name());
            }
            return $traverse_callback($current_type);
        });
    }
}