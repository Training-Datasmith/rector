<?php

declare (strict_types=1);
namespace Rector\Contract\Rector;

use Php_Parser\Node;
use Php_Parser\Node_Visitor;
interface Rector_Interface extends Node_Visitor
{
    /**
     * List of nodes this class checks, classes that implements \PhpParser\Node
     * See beautiful map of all nodes https://github.com/rectorphp/php-parser-nodes-docs#node-overview
     *
     * @return array<class-string<Node>>
     */
    public function get_node_types(): array;
    /**
     * Process Node of matched type
     * @return Node|Node[]|null|NodeVisitor::REMOVE_NODE
     */
    public function refactor(Node $node);
}