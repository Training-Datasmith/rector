<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Node_Factory;

use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Stmt\Use_;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Php80\Contract\Value_Object\Annotation_To_Attribute_Interface;
use Rector\Php_Attribute\Use_Alias_Name_Matcher;
use Rector\Php_Attribute\Value_Object\Use_Alias_Metadata;
final class Attribute_Name_Factory
{
    /**
     * @readonly
     */
    private Use_Alias_Name_Matcher $use_alias_name_matcher;
    public function __construct(Use_Alias_Name_Matcher $use_alias_name_matcher)
    {
        $this->use_alias_name_matcher = $use_alias_name_matcher;
    }
    /**
     * @param Use_[] $uses
     * @return \PhpParser\Node\Name\FullyQualified|\PhpParser\Node\Name
     */
    public function create(Annotation_To_Attribute_Interface $annotation_to_attribute, Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node, array $uses)
    {
        // A. attribute and class name are the same, so we re-use the short form to keep code compatible with previous one,
        // except start with \
        if ($annotation_to_attribute->get_attribute_class() === $annotation_to_attribute->get_tag()) {
            $attribute_name = $doctrine_annotation_tag_value_node->identifier_type_node->name;
            $attribute_name = ltrim($attribute_name, '@');
            if (strncmp($attribute_name, '\\', strlen('\\')) === 0) {
                return new Fully_Qualified(ltrim($attribute_name, '\\'));
            }
            return new Name($attribute_name);
        }
        // B. different name
        $use_alias_metadata = $this->use_alias_name_matcher->match($uses, $doctrine_annotation_tag_value_node->identifier_type_node->name, $annotation_to_attribute);
        if ($use_alias_metadata instanceof Use_Alias_Metadata) {
            $use_use = $use_alias_metadata->get_use_use();
            // is same as name?
            $use_import_name = $use_alias_metadata->get_use_import_name();
            if ($use_use->name->to_string() !== $use_import_name) {
                // no? rename
                $use_use->name = new Name($use_import_name);
            }
            return new Name($use_alias_metadata->get_short_attribute_name());
        }
        // 3. the class is not aliased and is completely new... return the FQN version
        return new Fully_Qualified($annotation_to_attribute->get_attribute_class());
    }
}