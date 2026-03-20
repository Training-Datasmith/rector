<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<ClassLike>
 */
final class Class_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Class_Like::class;
    }
    /**
     * @param ClassLike $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->namespaced_name instanceof Name) {
            return $node->namespaced_name->to_string();
        }
        if (!$node->name instanceof Identifier) {
            return null;
        }
        return $node->name->to_string();
    }
}