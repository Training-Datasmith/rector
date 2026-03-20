<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Manipulator;

use Php_Parser\Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Class_Annotation_Matcher;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation\Curly_List_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Enum\Class_Name;
use Rector\Renaming\Collector\Renamed_Name_Collector;
use Rector_Prefix202603\Nette\Utils\Strings;
final class Php_Doc_Class_Renamer
{
    /**
     * @readonly
     */
    private Class_Annotation_Matcher $class_annotation_matcher;
    /**
     * @readonly
     */
    private Renamed_Name_Collector $renamed_name_collector;
    public function __construct(Class_Annotation_Matcher $class_annotation_matcher, Renamed_Name_Collector $renamed_name_collector)
    {
        $this->class_annotation_matcher = $class_annotation_matcher;
        $this->renamed_name_collector = $renamed_name_collector;
    }
    /**
     * Covers annotations like @ORM, @Serializer, @Assert etc
     * See https://github.com/rectorphp/rector/issues/1872
     *
     * @param string[] $oldToNewClasses
     */
    public function change_type_in_annotation_types(Node $node, Php_Doc_Info $php_doc_info, array $old_to_new_classes, bool &$has_changed): bool
    {
        $this->process_assert_choice_tag_value_node($old_to_new_classes, $php_doc_info, $has_changed);
        $this->process_doctrine_relation_tag_value_node($node, $old_to_new_classes, $php_doc_info, $has_changed);
        $this->process_serializer_type_tag_value_node($old_to_new_classes, $php_doc_info, $has_changed);
        return $has_changed;
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function process_assert_choice_tag_value_node(array $old_to_new_classes, Php_Doc_Info $php_doc_info, bool &$has_changed): void
    {
        $assert_choice_doctrine_annotation_tag_value_node = $php_doc_info->find_one_by_annotation_class('Symfony\Component\Validator\Constraints\Choice');
        if (!$assert_choice_doctrine_annotation_tag_value_node instanceof Doctrine_Annotation_Tag_Value_Node) {
            return;
        }
        $callback_array_item_node = $assert_choice_doctrine_annotation_tag_value_node->get_value('callback');
        if (!$callback_array_item_node instanceof Array_Item_Node) {
            return;
        }
        $callback_class = $callback_array_item_node->value;
        // array is needed for callable
        if (!$callback_class instanceof Curly_List_Node) {
            return;
        }
        $callable_callback_array_items = $callback_class->get_values();
        $class_name_array_item_node = $callable_callback_array_items[0];
        $class_name_string_node = $class_name_array_item_node->value;
        if (!$class_name_string_node instanceof String_Node) {
            return;
        }
        foreach ($old_to_new_classes as $old_class => $new_class) {
            if ($class_name_string_node->value !== $old_class) {
                continue;
            }
            $this->renamed_name_collector->add($old_class);
            $class_name_string_node->value = $new_class;
            // trigger reprint
            $class_name_array_item_node->set_attribute(Php_Doc_Attribute_Key::ORIG_NODE, null);
            $has_changed = \true;
            break;
        }
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function process_doctrine_relation_tag_value_node(Node $node, array $old_to_new_classes, Php_Doc_Info $php_doc_info, bool &$has_changed): void
    {
        $doctrine_annotation_tag_value_node = $php_doc_info->get_by_annotation_classes(['Doctrine\ORM\Mapping\OneToMany', 'Doctrine\ORM\Mapping\ManyToMany', 'Doctrine\ORM\Mapping\Embedded']);
        if (!$doctrine_annotation_tag_value_node instanceof Doctrine_Annotation_Tag_Value_Node) {
            return;
        }
        $this->process_doctrine_to_many($doctrine_annotation_tag_value_node, $node, $old_to_new_classes, $has_changed);
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function process_serializer_type_tag_value_node(array $old_to_new_classes, Php_Doc_Info $php_doc_info, bool &$has_changed): void
    {
        $doctrine_annotation_tag_value_node = $php_doc_info->find_one_by_annotation_class(Class_Name::JMS_TYPE);
        if (!$doctrine_annotation_tag_value_node instanceof Doctrine_Annotation_Tag_Value_Node) {
            return;
        }
        $class_name_array_item_node = $doctrine_annotation_tag_value_node->get_silent_value();
        foreach ($old_to_new_classes as $old_class => $new_class) {
            if ($class_name_array_item_node instanceof Array_Item_Node && $class_name_array_item_node->value instanceof String_Node) {
                $class_name_string_node = $class_name_array_item_node->value;
                if ($class_name_string_node->value === $old_class) {
                    $class_name_string_node->value = $new_class;
                    continue;
                }
                $this->renamed_name_collector->add($old_class);
                $class_name_string_node->value = Strings::replace($class_name_string_node->value, '#\b' . preg_quote($old_class, '#') . 'b#', $new_class);
                $class_name_array_item_node->set_attribute(Php_Doc_Attribute_Key::ORIG_NODE, null);
                $has_changed = \true;
            }
            $current_type_array_item_node = $doctrine_annotation_tag_value_node->get_value('type');
            if (!$current_type_array_item_node instanceof Array_Item_Node) {
                continue;
            }
            $current_type_string_node = $current_type_array_item_node->value;
            if (!$current_type_string_node instanceof String_Node) {
                continue;
            }
            if ($current_type_string_node->value === $old_class) {
                $current_type_string_node->value = $new_class;
                $has_changed = \true;
            }
        }
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    private function process_doctrine_to_many(Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node, Node $node, array $old_to_new_classes, bool &$has_changed): void
    {
        $class_key = $doctrine_annotation_tag_value_node->has_class_name('Doctrine\ORM\Mapping\Embedded') ? 'class' : 'targetEntity';
        $target_entity_array_item_node = $doctrine_annotation_tag_value_node->get_value($class_key);
        if (!$target_entity_array_item_node instanceof Array_Item_Node) {
            return;
        }
        $target_entity_string_node = $target_entity_array_item_node->value;
        if (!$target_entity_string_node instanceof String_Node) {
            return;
        }
        $target_entity_class = $target_entity_string_node->value;
        // resolve to FQN
        $tag_fully_qualified_name = $this->class_annotation_matcher->resolve_tag_fully_qualified_name($target_entity_class, $node);
        foreach ($old_to_new_classes as $old_class => $new_class) {
            if ($tag_fully_qualified_name !== $old_class) {
                continue;
            }
            $this->renamed_name_collector->add($old_class);
            $target_entity_string_node->value = $new_class;
            $target_entity_array_item_node->set_attribute(Php_Doc_Attribute_Key::ORIG_NODE, null);
            $has_changed = \true;
        }
    }
}