<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver;

use Php_Stan\Type\Accessory\Accessory_Array_List_Type;
use Php_Stan\Type\Intersection_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Node_Type_Corrector\Accessory_Non_Empty_Array_Type_Corrector;
use Rector\Node_Type_Resolver\Node_Type_Corrector\Accessory_Non_Empty_String_Type_Corrector;
use Rector\Node_Type_Resolver\Node_Type_Corrector\Generic_Class_String_Type_Corrector;
/**
 * This service correct unnecessary intersection/union types that do not bring any value.
 * We focus on scalar types like "array", "string", "int" etc.,
 * to print them as valid type declarations.
 */
final class Node_Type_Corrector
{
    /**
     * @readonly
     */
    private Accessory_Non_Empty_String_Type_Corrector $accessory_non_empty_string_type_corrector;
    /**
     * @readonly
     */
    private Generic_Class_String_Type_Corrector $generic_class_string_type_corrector;
    /**
     * @readonly
     */
    private Accessory_Non_Empty_Array_Type_Corrector $accessory_non_empty_array_type_corrector;
    public function __construct(Accessory_Non_Empty_String_Type_Corrector $accessory_non_empty_string_type_corrector, Generic_Class_String_Type_Corrector $generic_class_string_type_corrector, Accessory_Non_Empty_Array_Type_Corrector $accessory_non_empty_array_type_corrector)
    {
        $this->accessory_non_empty_string_type_corrector = $accessory_non_empty_string_type_corrector;
        $this->generic_class_string_type_corrector = $generic_class_string_type_corrector;
        $this->accessory_non_empty_array_type_corrector = $accessory_non_empty_array_type_corrector;
    }
    public function correct_type(Type $type): Type
    {
        $type = $this->accessory_non_empty_string_type_corrector->correct($type);
        $type = $this->generic_class_string_type_corrector->correct($type);
        $type = $this->remove_accessory_array_list_type($type);
        return $this->accessory_non_empty_array_type_corrector->correct($type);
    }
    private function remove_accessory_array_list_type(Type $type): Type
    {
        if (!$type instanceof Intersection_Type) {
            return $type;
        }
        $clean_types = [];
        foreach ($type->get_types() as $intersection_type) {
            if ($intersection_type instanceof Accessory_Array_List_Type) {
                continue;
            }
            $clean_types[] = $intersection_type;
        }
        if (count($clean_types) === 1) {
            return $clean_types[0];
        }
        return new Intersection_Type($clean_types);
    }
}