<?php

declare (strict_types=1);
namespace Rector\Php_Attribute;

use Php_Parser\Node;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Stmt\Use_;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
use Php_Stan\Reflection\Reflection_Provider;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
use Rector\Better_Php_Doc_Parser\Php_Doc_Manipulator\Php_Doc_Tag_Remover;
use Rector\Naming\Naming\Use_Imports_Resolver;
use Rector\Php80\Node_Factory\Attr_Groups_Factory;
use Rector\Php80\Value_Object\Annotation_To_Attribute;
use Rector\Php80\Value_Object\Doctrine_Tag_And_Annotation_To_Attribute;
/**
 * @api used in Rector packages
 */
final class Generic_Annotation_To_Attribute_Converter
{
    /**
     * @readonly
     */
    private Attr_Groups_Factory $attr_groups_factory;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Use_Imports_Resolver $use_imports_resolver;
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    /**
     * @readonly
     */
    private Php_Doc_Tag_Remover $php_doc_tag_remover;
    public function __construct(Attr_Groups_Factory $attr_groups_factory, Reflection_Provider $reflection_provider, Use_Imports_Resolver $use_imports_resolver, Php_Doc_Info_Factory $php_doc_info_factory, Php_Doc_Tag_Remover $php_doc_tag_remover)
    {
        $this->attr_groups_factory = $attr_groups_factory;
        $this->reflection_provider = $reflection_provider;
        $this->use_imports_resolver = $use_imports_resolver;
        $this->php_doc_info_factory = $php_doc_info_factory;
        $this->php_doc_tag_remover = $php_doc_tag_remover;
    }
    public function convert(Node $node, Annotation_To_Attribute $annotation_to_attribute): ?Attribute_Group
    {
        if (!$this->is_existing_attribute_class($annotation_to_attribute)) {
            return null;
        }
        $php_doc_info = $this->php_doc_info_factory->create_from_node($node);
        if (!$php_doc_info instanceof Php_Doc_Info) {
            return null;
        }
        $uses = $this->use_imports_resolver->resolve_bare_uses();
        return $this->process_doctrine_annotation_class($php_doc_info, $uses, $annotation_to_attribute);
    }
    /**
     * @param Use_[] $uses
     */
    private function process_doctrine_annotation_class(Php_Doc_Info $php_doc_info, array $uses, Annotation_To_Attribute $annotation_to_attribute): ?Attribute_Group
    {
        if ($php_doc_info->get_php_doc_node()->children === []) {
            return null;
        }
        $doctrine_tag_and_annotation_to_attributes = [];
        $doctrine_tag_value_nodes = [];
        foreach ($php_doc_info->get_php_doc_node()->children as $php_doc_child_node) {
            if (!$php_doc_child_node instanceof Php_Doc_Tag_Node) {
                continue;
            }
            if (!$php_doc_child_node->value instanceof Doctrine_Annotation_Tag_Value_Node) {
                continue;
            }
            $doctrine_tag_value_node = $php_doc_child_node->value;
            if (!$doctrine_tag_value_node->has_class_name($annotation_to_attribute->get_tag())) {
                continue;
            }
            $doctrine_tag_and_annotation_to_attributes[] = new Doctrine_Tag_And_Annotation_To_Attribute($doctrine_tag_value_node, $annotation_to_attribute);
            $doctrine_tag_value_nodes[] = $doctrine_tag_value_node;
        }
        $attribute_groups = $this->attr_groups_factory->create($doctrine_tag_and_annotation_to_attributes, $uses);
        foreach ($doctrine_tag_value_nodes as $doctrine_tag_value_node) {
            $this->php_doc_tag_remover->remove_tag_value_from_node($php_doc_info, $doctrine_tag_value_node);
        }
        return $attribute_groups[0] ?? null;
    }
    private function is_existing_attribute_class(Annotation_To_Attribute $annotation_to_attribute): bool
    {
        // make sure the attribute class really exists to avoid error on early upgrade
        if (!$this->reflection_provider->has_class($annotation_to_attribute->get_attribute_class())) {
            return \false;
        }
        // make sure the class is marked as attribute
        $class_reflection = $this->reflection_provider->get_class($annotation_to_attribute->get_attribute_class());
        return $class_reflection->is_attribute_class();
    }
}