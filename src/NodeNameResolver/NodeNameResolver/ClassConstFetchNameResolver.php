<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Identifier;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<ClassConstFetch>
 */
final class Class_Const_Fetch_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Class_Const_Fetch::class;
    }
    /**
     * @param ClassConstFetch $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->class instanceof Expr) {
            return null;
        }
        if (!$node->name instanceof Identifier) {
            return null;
        }
        $class = $node->class->to_string();
        $name = $node->name->to_string();
        return $class . '::' . $name;
    }
}