<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Variable;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<Variable>
 */
final class Variable_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Variable::class;
    }
    /**
     * @param Variable $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->name instanceof Expr) {
            return null;
        }
        return $node->name;
    }
}