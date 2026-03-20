<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Contract\Php_Parser;

use Php_Parser\Node;
use Php_Stan\Type\Type;
/**
 * @template TNode as \PhpParser\Node
 */
interface Php_Parser_Node_Mapper_Interface
{
    /**
     * @return class-string<TNode>
     */
    public function get_node_type(): string;
    /**
     * @param TNode $node
     */
    public function map_to_php_stan(Node $node): Type;
}