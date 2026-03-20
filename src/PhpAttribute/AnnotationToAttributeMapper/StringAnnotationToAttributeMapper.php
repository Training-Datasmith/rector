<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Node\Scalar\String_;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
/**
 * @implements AnnotationToAttributeMapperInterface<string>
 */
final class String_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
{
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool
    {
        return is_string($value);
    }
    /**
     * @param string $value
     */
    public function map($value): String_
    {
        if (strpos($value, "'") !== \false && strpos($value, "\n") === \false) {
            $kind = String_::KIND_DOUBLE_QUOTED;
        } else {
            $kind = String_::KIND_SINGLE_QUOTED;
        }
        if (strncmp($value, '"', strlen('"')) === 0 && substr_compare($value, '"', -strlen('"')) === 0) {
            $value = trim($value, '"');
        }
        return new String_($value, [Attribute_Key::KIND => $kind]);
    }
}