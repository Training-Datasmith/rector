<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Enum\Object_Reference;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * @see \Rector\Tests\NodeTypeResolver\PerNodeTypeResolver\NameTypeResolver\NameTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Name|FullyQualified>
 */
final class Name_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Name::class, Fully_Qualified::class];
    }
    /**
     * @param Name $node
     */
    public function resolve(Node $node): Type
    {
        // not instanceof FullyQualified means it is a Name
        if (!$node instanceof Fully_Qualified && $node->has_attribute(Attribute_Key::NAMESPACED_NAME)) {
            return $this->resolve(new Fully_Qualified($node->get_attribute(Attribute_Key::NAMESPACED_NAME)));
        }
        if ($node->to_string() === Object_Reference::PARENT) {
            return $this->resolve_parent($node);
        }
        $fully_qualified_name = $this->resolve_fully_qualified_name($node);
        return new Object_Type($fully_qualified_name);
    }
    private function resolve_class_reflection(\Php_Parser\Node\Name $node): ?Class_Reflection
    {
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return null;
        }
        return $scope->get_class_reflection();
    }
    /**
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\ObjectType|\PHPStan\Type\UnionType
     */
    private function resolve_parent(Name $name)
    {
        $class_reflection = $this->resolve_class_reflection($name);
        if (!$class_reflection instanceof Class_Reflection || !$class_reflection->is_class()) {
            return new Mixed_Type();
        }
        if ($class_reflection->is_anonymous()) {
            return new Mixed_Type();
        }
        $parent_class_object_types = [];
        foreach ($class_reflection->get_parents() as $parent_class_reflection) {
            $parent_class_object_types[] = new Object_Type($parent_class_reflection->get_name());
        }
        if ($parent_class_object_types === []) {
            return new Mixed_Type();
        }
        if (count($parent_class_object_types) === 1) {
            return $parent_class_object_types[0];
        }
        return new Union_Type($parent_class_object_types);
    }
    private function resolve_fully_qualified_name(Name $name): string
    {
        $name_value = $name->to_string();
        if (in_array($name_value, [Object_Reference::SELF, Object_Reference::STATIC], \true)) {
            $class_reflection = $this->resolve_class_reflection($name);
            if (!$class_reflection instanceof Class_Reflection || $class_reflection->is_anonymous()) {
                return $name->to_string();
            }
            return $class_reflection->get_name();
        }
        return $name_value;
    }
}