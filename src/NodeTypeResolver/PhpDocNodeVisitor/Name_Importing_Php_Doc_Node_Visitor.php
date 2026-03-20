<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Doc_Node_Visitor;

use Php_Parser\Node as PhpParserNode;
use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Template_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Type;
use Rector\Application\Provider\Current_File_Provider;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Spaceless_Php_Doc_Tag_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skipper;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor;
use Rector\Post_Rector\Collector\Use_Nodes_To_Add_Collector;
use Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type;
use Rector\Value_Object\Application\File;
use Rector_Prefix202603\Nette\Utils\Strings;
final class Name_Importing_Php_Doc_Node_Visitor extends Abstract_Php_Doc_Node_Visitor
{
    /**
     * @readonly
     */
    private Class_Name_Import_Skipper $class_name_import_skipper;
    /**
     * @readonly
     */
    private Use_Nodes_To_Add_Collector $use_nodes_to_add_collector;
    /**
     * @readonly
     */
    private Current_File_Provider $current_file_provider;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper;
    private ?Php_Parser_Node $current_php_parser_node = null;
    private bool $has_changed = \false;
    public function __construct(Class_Name_Import_Skipper $class_name_import_skipper, Use_Nodes_To_Add_Collector $use_nodes_to_add_collector, Current_File_Provider $current_file_provider, Reflection_Provider $reflection_provider, Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper)
    {
        $this->class_name_import_skipper = $class_name_import_skipper;
        $this->use_nodes_to_add_collector = $use_nodes_to_add_collector;
        $this->current_file_provider = $current_file_provider;
        $this->reflection_provider = $reflection_provider;
        $this->identifier_php_doc_type_mapper = $identifier_php_doc_type_mapper;
    }
    public function before_traverse(\Php_Stan\Php_Doc_Parser\Ast\Node $node): void
    {
        if (!$this->current_php_parser_node instanceof Php_Parser_Node) {
            throw new Should_Not_Happen_Exception('Set "$currentPhpParserNode" first');
        }
    }
    public function enter_node(Node $node): ?Node
    {
        if ($node instanceof Spaceless_Php_Doc_Tag_Node) {
            return $this->enter_spaceless_php_doc_tag_node($node);
        }
        if ($node instanceof Doctrine_Annotation_Tag_Value_Node) {
            $this->process_doctrine_annotation_tag_value_node($node);
            return $node;
        }
        if (!$node instanceof Identifier_Type_Node) {
            return null;
        }
        if (!$this->current_php_parser_node instanceof Php_Parser_Node) {
            throw new Should_Not_Happen_Exception();
        }
        // no \, skip early
        if (strpos($node->name, '\\') === \false) {
            return null;
        }
        $static_type = $this->identifier_php_doc_type_mapper->map_identifier_type_node($node, $this->current_php_parser_node);
        $static_type = $this->resolve_fully_qualified($static_type);
        if (!$static_type instanceof Fully_Qualified_Object_Type) {
            return null;
        }
        $file = $this->current_file_provider->get_file();
        if (!$file instanceof File) {
            return null;
        }
        return $this->process_fqn_name_import($this->current_php_parser_node, $node, $static_type, $file);
    }
    public function set_current_node(Php_Parser_Node $php_parser_node): void
    {
        $this->has_changed = \false;
        $this->current_php_parser_node = $php_parser_node;
    }
    public function has_changed(): bool
    {
        return $this->has_changed;
    }
    private function resolve_fully_qualified(Type $type): ?Fully_Qualified_Object_Type
    {
        if ($type instanceof Shortened_Object_Type || $type instanceof Aliased_Object_Type) {
            return new Fully_Qualified_Object_Type($type->get_fully_qualified_name());
        }
        if ($type instanceof Fully_Qualified_Object_Type) {
            return $type;
        }
        return null;
    }
    private function process_fqn_name_import(Php_Parser_Node $php_parser_node, Identifier_Type_Node $identifier_type_node, Fully_Qualified_Object_Type $fully_qualified_object_type, File $file): ?Identifier_Type_Node
    {
        $parent_node = $identifier_type_node->get_attribute(Php_Doc_Attribute_Key::PARENT);
        if ($parent_node instanceof Template_Tag_Value_Node) {
            // might break
            return null;
        }
        // standardize to FQN
        if (strncmp($fully_qualified_object_type->get_class_name(), '@', strlen('@')) === 0) {
            $fully_qualified_object_type = new Fully_Qualified_Object_Type(ltrim($fully_qualified_object_type->get_class_name(), '@'));
        }
        if ($this->class_name_import_skipper->should_skip_name_for_fully_qualified_object_type($file, $php_parser_node, $fully_qualified_object_type)) {
            return null;
        }
        $new_node = new Identifier_Type_Node($fully_qualified_object_type->get_short_name());
        // should skip because its already used
        if ($this->use_nodes_to_add_collector->is_short_imported($file, $fully_qualified_object_type) && !$this->use_nodes_to_add_collector->is_import_shortable($file, $fully_qualified_object_type)) {
            return null;
        }
        if ($this->should_import($file, $new_node, $identifier_type_node, $fully_qualified_object_type)) {
            $this->use_nodes_to_add_collector->add_use_import($fully_qualified_object_type);
            $this->has_changed = \true;
            return $new_node;
        }
        return null;
    }
    private function should_import(File $file, Identifier_Type_Node $new_node, Identifier_Type_Node $identifier_type_node, Fully_Qualified_Object_Type $fully_qualified_object_type): bool
    {
        if ($new_node->name === $identifier_type_node->name) {
            return \false;
        }
        if (strncmp($identifier_type_node->name, '\\', strlen('\\')) === 0) {
            if ($fully_qualified_object_type->get_short_name() !== $fully_qualified_object_type->get_class_name()) {
                return $fully_qualified_object_type->get_short_name() !== ltrim($identifier_type_node->name, '\\');
            }
            return \true;
        }
        $class_name = $fully_qualified_object_type->get_class_name();
        if (!$this->reflection_provider->has_class($class_name)) {
            return \false;
        }
        $first_path = Strings::before($identifier_type_node->name, '\\' . $new_node->name);
        if ($first_path === null) {
            return !$this->use_nodes_to_add_collector->has_import($file, $fully_qualified_object_type);
        }
        if ($first_path === '') {
            return \true;
        }
        $namespace_parts = explode('\\', ltrim($first_path, '\\'));
        return count($namespace_parts) > 1;
    }
    private function process_doctrine_annotation_tag_value_node(Doctrine_Annotation_Tag_Value_Node $doctrine_annotation_tag_value_node): void
    {
        $current_php_parser_node = $this->current_php_parser_node;
        if (!$current_php_parser_node instanceof Php_Parser_Node) {
            throw new Should_Not_Happen_Exception();
        }
        $identifier_type_node = $doctrine_annotation_tag_value_node->identifier_type_node;
        $static_type = $this->identifier_php_doc_type_mapper->map_identifier_type_node($identifier_type_node, $current_php_parser_node);
        $static_type = $this->resolve_fully_qualified($static_type);
        if (!$static_type instanceof Fully_Qualified_Object_Type) {
            return;
        }
        $file = $this->current_file_provider->get_file();
        if (!$file instanceof File) {
            return;
        }
        $shortented_identifier_type_node = $this->process_fqn_name_import($current_php_parser_node, $identifier_type_node, $static_type, $file);
        if (!$shortented_identifier_type_node instanceof Identifier_Type_Node) {
            return;
        }
        $doctrine_annotation_tag_value_node->identifier_type_node = $shortented_identifier_type_node;
        $doctrine_annotation_tag_value_node->mark_as_changed();
    }
    private function enter_spaceless_php_doc_tag_node(Spaceless_Php_Doc_Tag_Node $spaceless_php_doc_tag_node): ?\Rector\Better_Php_Doc_Parser\Php_Doc\Spaceless_Php_Doc_Tag_Node
    {
        if (!$spaceless_php_doc_tag_node->value instanceof Doctrine_Annotation_Tag_Value_Node) {
            return null;
        }
        // special case for doctrine annotation
        if (strncmp($spaceless_php_doc_tag_node->name, '@', strlen('@')) !== 0) {
            return null;
        }
        $attribute_class = ltrim($spaceless_php_doc_tag_node->name, '@\\');
        $identifier_type_node = new Identifier_Type_Node($attribute_class);
        $current_php_parser_node = $this->current_php_parser_node;
        if (!$current_php_parser_node instanceof Php_Parser_Node) {
            throw new Should_Not_Happen_Exception();
        }
        $static_type = $this->identifier_php_doc_type_mapper->map_identifier_type_node(new Identifier_Type_Node($attribute_class), $current_php_parser_node);
        $static_type = $this->resolve_fully_qualified($static_type);
        if (!$static_type instanceof Fully_Qualified_Object_Type) {
            return null;
        }
        $file = $this->current_file_provider->get_file();
        if (!$file instanceof File) {
            return null;
        }
        $imported_name = $this->process_fqn_name_import($current_php_parser_node, $identifier_type_node, $static_type, $file);
        if ($imported_name instanceof Identifier_Type_Node) {
            $spaceless_php_doc_tag_node->name = '@' . $imported_name->name;
            return $spaceless_php_doc_tag_node;
        }
        return null;
    }
}