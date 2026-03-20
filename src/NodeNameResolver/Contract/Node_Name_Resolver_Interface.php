<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Contract;

use Php_Parser\Node;
use Php_Stan\Analyser\Scope;
/**
 * @template TNode as Node
 */
interface Node_Name_Resolver_Interface
{
    /**
     * @return class-string<TNode>
     */
    public function get_node(): string;
    /**
     * @param TNode $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string;
}