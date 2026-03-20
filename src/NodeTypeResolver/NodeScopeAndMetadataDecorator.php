<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver;

use Php_Parser\Node\Stmt;
use Php_Parser\Node_Traverser;
use Php_Parser\Node_Visitor\Cloning_Visitor;
use Rector\Node_Type_Resolver\Php_Stan\Scope\Php_Stan_Node_Scope_Resolver;
final class Node_Scope_And_Metadata_Decorator
{
    /**
     * @readonly
     */
    private Php_Stan_Node_Scope_Resolver $php_stan_node_scope_resolver;
    /**
     * @readonly
     */
    private Node_Traverser $node_traverser;
    public function __construct(Cloning_Visitor $cloning_visitor, Php_Stan_Node_Scope_Resolver $php_stan_node_scope_resolver)
    {
        $this->php_stan_node_scope_resolver = $php_stan_node_scope_resolver;
        // needed for format preserving printing
        $this->node_traverser = new Node_Traverser($cloning_visitor);
    }
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function decorate_nodes_from_file(string $file_path, array $stmts): array
    {
        $stmts = $this->php_stan_node_scope_resolver->process_nodes($stmts, $file_path);
        return $this->node_traverser->traverse($stmts);
    }
}