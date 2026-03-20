<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Stmt\Property;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * @see \Rector\Tests\NodeTypeResolver\PerNodeTypeResolver\PropertyTypeResolver\PropertyTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Property>
 */
final class Property_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @readonly
     */
    private \Rector\Node_Type_Resolver\Node_Type_Resolver\Property_Fetch_Type_Resolver $property_fetch_type_resolver;
    public function __construct(\Rector\Node_Type_Resolver\Node_Type_Resolver\Property_Fetch_Type_Resolver $property_fetch_type_resolver)
    {
        $this->property_fetch_type_resolver = $property_fetch_type_resolver;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function resolve(Node $node): Type
    {
        // fake property to local PropertyFetch → PHPStan understands that
        $property_fetch = new Property_Fetch(new Variable('this'), (string) $node->props[0]->name);
        $property_fetch->set_attribute(Attribute_Key::SCOPE, $node->get_attribute(Attribute_Key::SCOPE));
        return $this->property_fetch_type_resolver->resolve($property_fetch);
    }
}