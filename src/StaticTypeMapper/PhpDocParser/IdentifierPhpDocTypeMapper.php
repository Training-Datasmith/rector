<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Analyser\Name_Scope;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Float_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Iterable_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Static_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Enum\Object_Reference;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Reflection\Reflection_Resolver;
use Rector\Static_Type_Mapper\Contract\Php_Doc_Parser\Php_Doc_Type_Mapper_Interface;
use Rector\Static_Type_Mapper\Mapper\Scalar_String_To_Type_Mapper;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Parent_Static_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Self_Object_Type;
use Rector\Type_Declaration\Php_Stan\Object_Type_Specifier;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @implements PhpDocTypeMapperInterface<IdentifierTypeNode>
 */
final class Identifier_Php_Doc_Type_Mapper implements Php_Doc_Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private Object_Type_Specifier $object_type_specifier;
    /**
     * @readonly
     */
    private Scalar_String_To_Type_Mapper $scalar_string_to_type_mapper;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    public function __construct(Object_Type_Specifier $object_type_specifier, Scalar_String_To_Type_Mapper $scalar_string_to_type_mapper, Reflection_Provider $reflection_provider, Reflection_Resolver $reflection_resolver)
    {
        $this->object_type_specifier = $object_type_specifier;
        $this->scalar_string_to_type_mapper = $scalar_string_to_type_mapper;
        $this->reflection_provider = $reflection_provider;
        $this->reflection_resolver = $reflection_resolver;
    }
    public function get_node_type(): string
    {
        return Identifier_Type_Node::class;
    }
    /**
     * @param IdentifierTypeNode $typeNode
     */
    public function map_to_php_stan_type(Type_Node $type_node, Node $node, Name_Scope $name_scope): Type
    {
        return $this->map_identifier_type_node($type_node, $node);
    }
    public function map_identifier_type_node(Identifier_Type_Node $identifier_type_node, Node $node): Type
    {
        $type = $this->scalar_string_to_type_mapper->map_scalar_string_to_type($identifier_type_node->name);
        if (!$type instanceof Mixed_Type) {
            return $type;
        }
        if ($type->is_explicit_mixed()) {
            return $type;
        }
        $lowered_name = strtolower($identifier_type_node->name);
        if ($lowered_name === Object_Reference::SELF) {
            return $this->map_self($node);
        }
        if ($lowered_name === Object_Reference::PARENT) {
            return $this->map_parent($node);
        }
        if ($lowered_name === Object_Reference::STATIC) {
            return $this->map_static($node);
        }
        if ($lowered_name === 'iterable') {
            return new Iterable_Type(new Mixed_Type(), new Mixed_Type());
        }
        $with_preslash = \false;
        if (strncmp($identifier_type_node->name, '\\', strlen('\\')) === 0) {
            $type_without_preslash = Strings::substring($identifier_type_node->name, 1);
            $object_type = new Fully_Qualified_Object_Type($type_without_preslash);
            $with_preslash = \true;
        } else {
            if ($identifier_type_node->name === 'scalar') {
                // pseudo type, see https://www.php.net/manual/en/language.types.intro.php
                $scalar_types = [new Boolean_Type(), new String_Type(), new Integer_Type(), new Float_Type()];
                return new Union_Type($scalar_types);
            }
            $object_type = new Object_Type(ltrim($identifier_type_node->name, '@'));
        }
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        return $this->object_type_specifier->narrow_to_fully_qualified_or_aliased_object_type($node, $object_type, $scope, $with_preslash);
    }
    /**
     * @return \PHPStan\Type\MixedType|\Rector\StaticTypeMapper\ValueObject\Type\SelfObjectType
     */
    private function map_self(Node $node)
    {
        // @todo check FQN
        $class_name = $this->resolve_class_name($node);
        if (!is_string($class_name)) {
            // self outside the class, e.g. in a function
            return new Mixed_Type();
        }
        return new Self_Object_Type($class_name);
    }
    /**
     * @return \Rector\StaticTypeMapper\ValueObject\Type\ParentStaticType|\PHPStan\Type\MixedType
     */
    private function map_parent(Node $node)
    {
        $class_name = $this->resolve_class_name($node);
        if (!is_string($class_name)) {
            // parent outside the class, e.g. in a function
            return new Mixed_Type();
        }
        if (!$this->reflection_provider->has_class($class_name)) {
            return new Mixed_Type();
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        $parent_class_reflection = $class_reflection->get_parent_class();
        if (!$parent_class_reflection instanceof Class_Reflection) {
            return new Mixed_Type();
        }
        return new Parent_Static_Type($parent_class_reflection);
    }
    /**
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\StaticType
     */
    private function map_static(Node $node)
    {
        $class_name = $this->resolve_class_name($node);
        if (!is_string($class_name)) {
            // static outside the class, e.g. in a function
            return new Mixed_Type();
        }
        if (!$this->reflection_provider->has_class($class_name)) {
            return new Mixed_Type();
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        return new Static_Type($class_reflection);
    }
    private function resolve_class_name(Node $node): ?string
    {
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($node);
        if (!$class_reflection instanceof Class_Reflection) {
            return null;
        }
        return $class_reflection->get_name();
    }
}