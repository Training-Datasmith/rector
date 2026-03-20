<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Static_Type;
use Php_Stan\Type\Type;
use Rector\Enum\Object_Reference;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Reflection\Reflection_Resolver;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
use Rector\Static_Type_Mapper\Value_Object\Type\Parent_Object_Without_Class_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Parent_Static_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Self_Static_Type;
/**
 * @implements PhpParserNodeMapperInterface<Name>
 */
final class Name_Node_Mapper implements Php_Parser_Node_Mapper_Interface
{
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Parser\Fully_Qualified_Node_Mapper $fully_qualified_node_mapper;
    public function __construct(Reflection_Resolver $reflection_resolver, \Rector\Static_Type_Mapper\Php_Parser\Fully_Qualified_Node_Mapper $fully_qualified_node_mapper)
    {
        $this->reflection_resolver = $reflection_resolver;
        $this->fully_qualified_node_mapper = $fully_qualified_node_mapper;
    }
    public function get_node_type(): string
    {
        return Name::class;
    }
    /**
     * @param Name $node
     */
    public function map_to_php_stan(Node $node): Type
    {
        $name = $node->to_string();
        if ($node->is_special_class_name()) {
            return $this->create_class_reference_type($node, $name);
        }
        $expanded_namespaced_name = $this->expanded_namespaced_name($node);
        if ($expanded_namespaced_name instanceof Fully_Qualified) {
            return $this->fully_qualified_node_mapper->map_to_php_stan($expanded_namespaced_name);
        }
        return new Mixed_Type();
    }
    private function expanded_namespaced_name(Name $name): ?Fully_Qualified
    {
        if (get_class($name) !== Name::class) {
            return null;
        }
        if (!$name->has_attribute(Attribute_Key::NAMESPACED_NAME)) {
            return null;
        }
        return new Fully_Qualified($name->get_attribute(Attribute_Key::NAMESPACED_NAME));
    }
    /**
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\StaticType|\Rector\StaticTypeMapper\ValueObject\Type\SelfStaticType|\PHPStan\Type\ObjectWithoutClassType
     */
    private function create_class_reference_type(Name $name, string $reference)
    {
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($name);
        if (!$class_reflection instanceof Class_Reflection) {
            return new Mixed_Type();
        }
        if ($reference === Object_Reference::STATIC) {
            return new Static_Type($class_reflection);
        }
        if ($reference === Object_Reference::SELF) {
            return new Self_Static_Type($class_reflection);
        }
        $parent_class_reflection = $class_reflection->get_parent_class();
        if ($parent_class_reflection instanceof Class_Reflection) {
            return new Parent_Static_Type($parent_class_reflection);
        }
        return new Parent_Object_Without_Class_Type();
    }
}