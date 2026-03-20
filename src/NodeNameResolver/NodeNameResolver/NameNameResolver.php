<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<Name>
 */
final class Name_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Name::class;
    }
    /**
     * @param Name $node
     */
    public function resolve(Node $node, ?Scope $scope): string
    {
        return $node->to_string();
    }
}