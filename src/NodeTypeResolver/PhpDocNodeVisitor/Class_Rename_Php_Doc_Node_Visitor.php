<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Doc_Node_Visitor;

use Php_Parser\Node as PhpNode;
use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Generic\Template_Object_Type;
use Php_Stan\Type\Object_Type;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Type_Resolver\Value_Object\Old_To_New_Type;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor;
use Rector\Renaming\Collector\Renamed_Name_Collector;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type;
final class Class_Rename_Php_Doc_Node_Visitor extends Abstract_Php_Doc_Node_Visitor
{
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private Renamed_Name_Collector $renamed_name_collector;
    /**
     * @var OldToNewType[]
     */
    private array $old_to_new_types = [];
    private bool $has_changed = \false;
    private ?Php_Node $current_php_node = null;
    public function __construct(Static_Type_Mapper $static_type_mapper, Renamed_Name_Collector $renamed_name_collector)
    {
        $this->static_type_mapper = $static_type_mapper;
        $this->renamed_name_collector = $renamed_name_collector;
    }
    public function set_current_php_node(Php_Node $php_node): void
    {
        $this->current_php_node = $php_node;
    }
    public function before_traverse(Node $node): void
    {
        if ($this->old_to_new_types === []) {
            throw new Should_Not_Happen_Exception('Configure "$oldToNewClasses" first');
        }
        if (!$this->current_php_node instanceof Php_Node) {
            throw new Should_Not_Happen_Exception('Configure "$currentPhpNode" first');
        }
        $this->has_changed = \false;
    }
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Identifier_Type_Node) {
            return null;
        }
        /** @var \PhpParser\Node $currentPhpNode */
        $current_php_node = $this->current_php_node;
        $static_type = $this->static_type_mapper->map_php_stan_php_doc_type_node_to_php_stan_type($node, $current_php_node);
        // non object type and @template is to not be renamed
        if (!$static_type instanceof Object_Type || $static_type instanceof Template_Object_Type) {
            return null;
        }
        // make sure to compare FQNs
        $object_type = $this->ensure_fqcn_object($static_type, $node->name);
        foreach ($this->old_to_new_types as $old_to_new_type) {
            $old_type = $old_to_new_type->get_old_type();
            if (!$old_type instanceof Object_Type) {
                continue;
            }
            if (!$object_type->equals($old_type)) {
                continue;
            }
            $new_type_node = $this->static_type_mapper->map_php_stan_type_to_php_stan_php_doc_type_node($old_to_new_type->get_new_type());
            $parent_type = $node->get_attribute(Php_Doc_Attribute_Key::PARENT);
            if ($parent_type instanceof Type_Node) {
                // mirror attributes
                $new_type_node->set_attribute(Php_Doc_Attribute_Key::PARENT, $parent_type);
            }
            $this->has_changed = \true;
            $this->renamed_name_collector->add($old_type->get_class_name());
            return $new_type_node;
        }
        return null;
    }
    /**
     * @param OldToNewType[] $oldToNewTypes
     */
    public function set_old_to_new_types(array $old_to_new_types): void
    {
        $this->old_to_new_types = $old_to_new_types;
    }
    public function has_changed(): bool
    {
        return $this->has_changed;
    }
    private function ensure_fqcn_object(Object_Type $object_type, string $identifier_name): Object_Type
    {
        if ($object_type instanceof Shortened_Object_Type && strncmp($identifier_name, '\\', strlen('\\')) === 0) {
            return new Object_Type(ltrim($identifier_name, '\\'));
        }
        if ($object_type instanceof Shortened_Object_Type || $object_type instanceof Aliased_Object_Type) {
            return new Object_Type($object_type->get_fully_qualified_name());
        }
        return $object_type;
    }
}