<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Node_Traverser;

use Php_Parser\Node;
use Php_Parser\Node_Traverser;
use Rector\Php_Doc_Parser\Node_Visitor\Callable_Node_Visitor;
/**
 * @api
 */
final class Simple_Callable_Node_Traverser
{
    /**
     * @param Node|Node[]|null $node
     *
     * @param callable(Node $node): (int|Node|null|Node[]) $callable
     * @api shortcut helper
     */
    public static function traverse($node, callable $callable): void
    {
        self::traverse_nodes_with_callable($node, $callable);
    }
    /**
     * @param callable(Node $node): (int|Node|null|Node[]) $callable
     * @param Node|Node[]|null $node
     */
    public static function traverse_nodes_with_callable($node, callable $callable): void
    {
        if ($node === null || $node === []) {
            return;
        }
        $callable_node_visitor = new Callable_Node_Visitor($callable);
        $node_traverser = new Node_Traverser($callable_node_visitor);
        $nodes = $node instanceof Node ? [$node] : $node;
        $node_traverser->traverse($nodes);
    }
}