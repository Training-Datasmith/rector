<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\String_;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
use Rector\Validation\Rector_Assert;
use Rector_Prefix202603\Webmozart\Assert\InvalidArgumentException;
/**
 * @implements AnnotationToAttributeMapperInterface<string>
 */
final class Class_Const_Fetch_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
{
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool
    {
        if (!is_string($value)) {
            return \false;
        }
        if (strpos($value, '::') === \false) {
            return \false;
        }
        // is quoted? skip it
        return strncmp($value, '"', strlen('"')) !== 0;
    }
    /**
     * @param string $value
     * @return String_|ClassConstFetch
     */
    public function map($value): Node
    {
        $values = explode('::', $value);
        if (count($values) !== 2) {
            return new String_($value);
        }
        [$class, $constant] = $values;
        if ($class === '') {
            return new String_($value);
        }
        try {
            Rector_Assert::class_name(ltrim($class, '\\'));
            Rector_Assert::constant_name($constant);
        } catch (InvalidArgumentException $exception) {
            return new String_($value);
        }
        return new Class_Const_Fetch(new Name($class), $constant);
    }
}