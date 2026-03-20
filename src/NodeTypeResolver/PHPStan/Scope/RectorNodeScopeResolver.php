<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan\Scope;

use Php_Parser\Node;
use Php_Parser\Node\Stmt;
use Php_Stan\Analyser\Mutating_Scope;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
/**
 * Handle Scope filling when there is error \PHPStan\Parser\ParserErrorsException
 * from PHPStan NodeScopeResolver
 */
final class Rector_Node_Scope_Resolver
{
    /**
     * @param Stmt[] $stmts
     */
    public static function process_nodes(array $stmts, Mutating_Scope $mutating_scope): void
    {
        $simple_callable_node_traverser = new Simple_Callable_Node_Traverser();
        $simple_callable_node_traverser->traverse_nodes_with_callable($stmts, function (Node $node) use ($mutating_scope) {
            $node->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
            return null;
        });
    }
}