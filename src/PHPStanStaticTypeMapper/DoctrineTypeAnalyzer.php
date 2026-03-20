<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper;

use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
final class Doctrine_Type_Analyzer
{
    public function is_doctrine_collection_with_iterable_union_type(Type $type): bool
    {
        if (!$type instanceof Union_Type) {
            return \false;
        }
        $is_array_type = \false;
        $has_doctrine_collection_type = \false;
        foreach ($type->get_types() as $unioned_type) {
            if ($this->is_instance_of_collection_type($unioned_type)) {
                $has_doctrine_collection_type = \true;
            }
            if ($unioned_type->is_array()->yes()) {
                $is_array_type = \true;
            }
        }
        if (!$has_doctrine_collection_type) {
            return \false;
        }
        return $is_array_type;
    }
    public function is_instance_of_collection_type(Type $type): bool
    {
        if (!$type instanceof Object_Type) {
            return \false;
        }
        return $type->is_instance_of('Doctrine\Common\Collections\Collection')->yes();
    }
}