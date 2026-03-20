<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node_Visitor;
use Php_Parser\Node_Visitor_Abstract;
final class Callable_Node_Visitor extends Node_Visitor_Abstract
{
    /**
     * @var callable(Node): (int|Node|null|Node[])
     */
    private $callable;
    /**
     * @param callable(Node $node): (int|Node|null|Node[]) $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }
    /**
     * @return NodeVisitor::*|Node|null|Node[]
     */
    public function enter_node(Node $node)
    {
        $original_node = $node;
        $callable = $this->callable;
        /** @var NodeVisitor::*|Node|null|Node[] $newNode */
        $new_node = $callable($node);
        if ($original_node instanceof Stmt && $new_node instanceof Expr) {
            return new Expression($new_node);
        }
        return $new_node;
    }
}