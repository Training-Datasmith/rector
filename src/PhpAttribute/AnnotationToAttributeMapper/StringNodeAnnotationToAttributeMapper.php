<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Node\Scalar\String_;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
/**
 * @implements AnnotationToAttributeMapperInterface<StringNode>
 */
final class String_Node_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
{
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool
    {
        return $value instanceof String_Node;
    }
    /**
     * @param StringNode $value
     */
    public function map($value): String_
    {
        return new String_($value->value, [Attribute_Key::KIND => $value->get_attribute(Attribute_Key::KIND)]);
    }
}