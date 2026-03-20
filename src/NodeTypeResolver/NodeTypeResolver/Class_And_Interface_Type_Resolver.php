<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * @see \Rector\Tests\NodeTypeResolver\PerNodeTypeResolver\ClassAndInterfaceTypeResolver\ClassTypeResolverTest
 * @see \Rector\Tests\NodeTypeResolver\PerNodeTypeResolver\ClassAndInterfaceTypeResolver\InterfaceTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Class_|Interface_>
 */
final class Class_And_Interface_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver)
    {
        $this->node_name_resolver = $node_name_resolver;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Class_::class, Interface_::class];
    }
    /**
     * @param Class_|Interface_ $node
     */
    public function resolve(Node $node): Type
    {
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            // new node probably
            return new Mixed_Type();
        }
        $class_reflection = $scope->get_class_reflection();
        if (!$class_reflection instanceof Class_Reflection) {
            return new Object_Type((string) $this->node_name_resolver->get_name($node));
        }
        return new Object_Type($class_reflection->get_name(), null, $class_reflection);
    }
}