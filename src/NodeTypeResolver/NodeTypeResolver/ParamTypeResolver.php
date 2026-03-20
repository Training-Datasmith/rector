<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Param;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Aware_Interface;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
/**
 * @see \Rector\Tests\NodeTypeResolver\PerNodeTypeResolver\ParamTypeResolver\ParamTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Param>
 */
final class Param_Type_Resolver implements Node_Type_Resolver_Interface, Node_Type_Resolver_Aware_Interface
{
    private Node_Type_Resolver $node_type_resolver;
    public function autowire(Node_Type_Resolver $node_type_resolver): void
    {
        $this->node_type_resolver = $node_type_resolver;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Param::class];
    }
    /**
     * @param Param $node
     */
    public function resolve(Node $node): Type
    {
        if ($node->type === null) {
            return new Mixed_Type();
        }
        return $this->node_type_resolver->get_type($node->type);
    }
}