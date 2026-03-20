<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Analyser\Scope;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * @implements NodeTypeResolverInterface<ClassConstFetch>
 */
final class Class_Const_Fetch_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Class_Const_Fetch::class];
    }
    /**
     * @param ClassConstFetch $node
     */
    public function resolve(Node $node): Type
    {
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return new Mixed_Type();
        }
        if ($node->class instanceof Fully_Qualified) {
            return $scope->get_type($node);
        }
        if ($node->class instanceof Name && $node->class->has_attribute(Attribute_Key::NAMESPACED_NAME)) {
            $new_node = clone $node;
            $new_node->class = new Fully_Qualified($node->class->get_attribute(Attribute_Key::NAMESPACED_NAME));
            return $scope->get_type($new_node);
        }
        return $scope->get_type($node);
    }
}