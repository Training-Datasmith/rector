<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Static_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Php\Php_Method_Reflection;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Aware_Interface;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Node_Type_Resolver\Php_Stan\Parameters_Acceptor_Selector_Variants_Wrapper;
/**
 * @implements NodeTypeResolverInterface<StaticCall|MethodCall>
 */
final class Static_Call_Method_Call_Type_Resolver implements Node_Type_Resolver_Interface, Node_Type_Resolver_Aware_Interface
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    private Node_Type_Resolver $node_type_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver)
    {
        $this->node_name_resolver = $node_name_resolver;
    }
    public function autowire(Node_Type_Resolver $node_type_resolver): void
    {
        $this->node_type_resolver = $node_type_resolver;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Static_Call::class, Method_Call::class];
    }
    /**
     * @param StaticCall|MethodCall $node
     */
    public function resolve(Node $node): Type
    {
        $method_name = $this->node_name_resolver->get_name($node->name);
        // no specific method found, return class types, e.g. <ClassType>::$method()
        if (!is_string($method_name)) {
            return new Mixed_Type();
        }
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return new Mixed_Type();
        }
        $node_return_type = $scope->get_type($node);
        if (!$node_return_type instanceof Mixed_Type) {
            return $node_return_type;
        }
        if ($node instanceof Method_Call) {
            $caller_type = $this->node_type_resolver->get_type($node->var);
        } else {
            $caller_type = $this->node_type_resolver->get_type($node->class);
        }
        foreach ($caller_type->get_object_class_reflections() as $object_class_reflection) {
            $class_method_return_type = $this->resolve_class_method_return_type($object_class_reflection, $node, $method_name, $scope);
            if (!$class_method_return_type instanceof Mixed_Type) {
                return $class_method_return_type;
            }
        }
        return new Mixed_Type();
    }
    /**
     * @param \PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\MethodCall $node
     */
    private function resolve_class_method_return_type(Class_Reflection $class_reflection, \Php_Parser\Node $node, string $method_name, Scope $scope): Type
    {
        foreach ($class_reflection->get_ancestors() as $ancestor_class_reflection) {
            if (!$ancestor_class_reflection->has_method($method_name)) {
                continue;
            }
            $method_reflection = $ancestor_class_reflection->get_method($method_name, $scope);
            if ($method_reflection instanceof Php_Method_Reflection) {
                $parameters_acceptor_with_php_docs = Parameters_Acceptor_Selector_Variants_Wrapper::select($method_reflection, $node, $scope);
                return $parameters_acceptor_with_php_docs->get_return_type();
            }
        }
        return new Mixed_Type();
    }
}