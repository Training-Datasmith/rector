<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<ClassConst>
 */
final class Class_Const_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Class_Const::class;
    }
    /**
     * @param ClassConst $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->consts === []) {
            return null;
        }
        $only_constant = $node->consts[0];
        return $only_constant->name->to_string();
    }
}