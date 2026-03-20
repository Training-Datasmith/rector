<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Stmt\Class_;
use Php_Stan\Better_Reflection\Reflection\Adapter\ReflectionClass;
use Php_Stan\Reflection\Class_Reflection;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
/**
 * @api used in doctrine
 */
final class Doctrine_Entity_Analyzer
{
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    /**
     * @var string[]
     */
    private const DOCTRINE_MAPPING_CLASSES = ['Doctrine\ORM\Mapping\Entity', 'Doctrine\ORM\Mapping\Embeddable', 'Doctrine\ODM\MongoDB\Mapping\Annotations\Document', 'Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument'];
    public function __construct(Php_Doc_Info_Factory $php_doc_info_factory)
    {
        $this->php_doc_info_factory = $php_doc_info_factory;
    }
    public function has_class_annotation(Class_ $class): bool
    {
        $php_doc_info = $this->php_doc_info_factory->create_from_node($class);
        if (!$php_doc_info instanceof Php_Doc_Info) {
            return \false;
        }
        return $php_doc_info->has_by_annotation_classes(self::DOCTRINE_MAPPING_CLASSES);
    }
    public function has_class_reflection_attribute(Class_Reflection $class_reflection): bool
    {
        /** @var ReflectionClass $nativeReflectionClass */
        $native_reflection_class = $class_reflection->get_native_reflection();
        // skip early in case of no attributes at all
        if ((method_exists($native_reflection_class, 'getAttributes') ? $native_reflection_class->get_attributes() : []) === []) {
            return \false;
        }
        foreach (self::DOCTRINE_MAPPING_CLASSES as $doctrine_mapping_class) {
            // skip entities
            if ((method_exists($native_reflection_class, 'getAttributes') ? $native_reflection_class->get_attributes($doctrine_mapping_class) : []) !== []) {
                return \true;
            }
        }
        return \false;
    }
}