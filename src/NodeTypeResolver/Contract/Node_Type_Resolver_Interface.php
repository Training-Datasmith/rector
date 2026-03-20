<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Contract;

use Php_Parser\Node;
use Php_Stan\Type\Type;
/**
 * @template TNode as \PhpParser\Node
 */
interface Node_Type_Resolver_Interface
{
    /**
     * @return array<class-string<TNode>>
     */
    public function get_node_classes(): array;
    /**
     * @param TNode $node
     */
    public function resolve(Node $node): Type;
}