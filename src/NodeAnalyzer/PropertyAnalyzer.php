<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Stmt\Property;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
use Rector\Static_Type_Mapper\Value_Object\Type\Non_Existing_Object_Type;
final class Property_Analyzer
{
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    public function __construct(Node_Type_Resolver $node_type_resolver)
    {
        $this->node_type_resolver = $node_type_resolver;
    }
    public function has_forbidden_type(Property $property): bool
    {
        $property_type = $this->node_type_resolver->get_type($property);
        if ($property_type->is_null()->yes()) {
            return \true;
        }
        if ($this->is_forbidden_type($property_type)) {
            return \true;
        }
        if (!$property_type instanceof Union_Type) {
            return \false;
        }
        $types = $property_type->get_types();
        foreach ($types as $type) {
            if ($this->is_forbidden_type($type)) {
                return \true;
            }
        }
        return \false;
    }
    public function is_forbidden_type(Type $type): bool
    {
        if ($type instanceof Non_Existing_Object_Type) {
            return \true;
        }
        return $this->is_callable_type($type);
    }
    private function is_callable_type(Type $type): bool
    {
        if (Class_Name_From_Object_Type_Resolver::resolve($type) === 'Closure') {
            return \false;
        }
        return $type->is_callable()->yes();
    }
}