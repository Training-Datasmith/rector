<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Aware_Interface;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
/**
 * @see \Rector\Tests\NodeTypeResolver\PerNodeTypeResolver\PropertyFetchTypeResolver\PropertyFetchTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<PropertyFetch>
 */
final class Property_Fetch_Type_Resolver implements Node_Type_Resolver_Interface, Node_Type_Resolver_Aware_Interface
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    private Node_Type_Resolver $node_type_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver, Reflection_Provider $reflection_provider)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->reflection_provider = $reflection_provider;
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
        return [Property_Fetch::class];
    }
    /**
     * @param PropertyFetch $node
     */
    public function resolve(Node $node): Type
    {
        // compensate 3rd party non-analysed property reflection
        $vendor_property_type = $this->get_vendor_property_fetch_type($node);
        if (!$vendor_property_type instanceof Mixed_Type) {
            return $vendor_property_type;
        }
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return new Mixed_Type();
        }
        return $scope->get_type($node);
    }
    private function get_vendor_property_fetch_type(Property_Fetch $property_fetch): Type
    {
        // 3rd party code
        $property_name = $this->node_name_resolver->get_name($property_fetch->name);
        if ($property_name === null) {
            return new Mixed_Type();
        }
        $var_type = $this->node_type_resolver->get_type($property_fetch->var);
        if (!$var_type instanceof Object_Type) {
            return new Mixed_Type();
        }
        if (!$this->reflection_provider->has_class($var_type->get_class_name())) {
            return new Mixed_Type();
        }
        $class_reflection = $this->reflection_provider->get_class($var_type->get_class_name());
        if (!$class_reflection->has_instance_property($property_name)) {
            return new Mixed_Type();
        }
        $property_fetch_scope = $property_fetch->get_attribute(Attribute_Key::SCOPE);
        if (!$property_fetch_scope instanceof Scope) {
            return new Mixed_Type();
        }
        $extended_property_reflection = $class_reflection->get_instance_property($property_name, $property_fetch_scope);
        return $extended_property_reflection->get_readable_type();
    }
}