<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Property;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<Property>
 */
final class Property_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Property::class;
    }
    /**
     * @param Property $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->props === []) {
            return null;
        }
        $only_property = $node->props[0];
        return $only_property->name->to_string();
    }
}