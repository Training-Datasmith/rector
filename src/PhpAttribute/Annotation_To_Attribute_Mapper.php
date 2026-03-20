<?php

declare (strict_types=1);
namespace Rector\Php_Attribute;

use Php_Parser\Builder_Helpers;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Scalar\String_;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
use Rector\Php_Attribute\Enum\Doc_Tag_Node_State;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\PhpAttribute\AnnotationToAttributeMapper\AnnotationToAttributeMapperTest
 */
final class Annotation_To_Attribute_Mapper
{
    /**
     * @var AnnotationToAttributeMapperInterface[]
     * @readonly
     */
    private array $annotation_to_attribute_mappers;
    /**
     * @param AnnotationToAttributeMapperInterface[] $annotationToAttributeMappers
     */
    public function __construct(array $annotation_to_attribute_mappers)
    {
        $this->annotation_to_attribute_mappers = $annotation_to_attribute_mappers;
        Assert::not_empty($annotation_to_attribute_mappers);
    }
    /**
     * @return mixed|DocTagNodeState::REMOVE_ARRAY
     * @param mixed $value
     */
    public function map($value)
    {
        foreach ($this->annotation_to_attribute_mappers as $annotation_to_attribute_mapper) {
            if ($annotation_to_attribute_mapper->is_candidate($value)) {
                return $annotation_to_attribute_mapper->map($value);
            }
        }
        if ($value instanceof Expr) {
            return $value;
        }
        // remove node, as handled elsewhere
        if ($value instanceof Doctrine_Annotation_Tag_Value_Node) {
            return Doc_Tag_Node_State::REMOVE_ARRAY;
        }
        if ($value instanceof Array_Item_Node) {
            return Builder_Helpers::normalize_value((string) $value);
        }
        if ($value instanceof String_Node) {
            return new String_($value->value, [Attribute_Key::KIND => $value->get_attribute(Attribute_Key::KIND)]);
        }
        // fallback
        return Builder_Helpers::normalize_value($value);
    }
}