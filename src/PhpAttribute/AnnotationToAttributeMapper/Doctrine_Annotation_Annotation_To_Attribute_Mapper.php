<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Name;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Attribute_Array_Name_Inliner;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @implements AnnotationToAttributeMapperInterface<DoctrineAnnotationTagValueNode>
 */
final class Doctrine_Annotation_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
{
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @readonly
     */
    private Attribute_Array_Name_Inliner $attribute_array_name_inliner;
    private Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper;
    public function __construct(Php_Version_Provider $php_version_provider, Attribute_Array_Name_Inliner $attribute_array_name_inliner)
    {
        $this->php_version_provider = $php_version_provider;
        $this->attribute_array_name_inliner = $attribute_array_name_inliner;
    }
    /**
     * Avoid circular reference
     */
    public function autowire(Annotation_To_Attribute_Mapper $annotation_to_attribute_mapper): void
    {
        $this->annotation_to_attribute_mapper = $annotation_to_attribute_mapper;
    }
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool
    {
        if (!$value instanceof Doctrine_Annotation_Tag_Value_Node) {
            return \false;
        }
        return $this->php_version_provider->is_at_least_php_version(Php_Version_Feature::NEW_INITIALIZERS);
    }
    /**
     * @param DoctrineAnnotationTagValueNode $value
     */
    public function map($value): New_
    {
        $annotation_short_name = $this->resolve_annotation_name($value);
        $values = $value->get_values();
        if ($values !== []) {
            $arg_values = $this->annotation_to_attribute_mapper->map($value->get_values());
            if ($arg_values instanceof Array_) {
                // create named args
                $args = $this->attribute_array_name_inliner->inline_array_to_args($arg_values);
            } else {
                throw new Should_Not_Happen_Exception();
            }
        } else {
            $args = [];
        }
        return new New_(new Name($annotation_short_name), $args);
    }
    private function resolve_annotation_name(Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node): string
    {
        $annotation_short_name = $doctrine_annotation_tag_value_node->identifier_type_node->name;
        return ltrim($annotation_short_name, '@');
    }
}