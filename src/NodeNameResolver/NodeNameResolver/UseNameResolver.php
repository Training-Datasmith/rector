<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Use_;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<Use_>
 */
final class Use_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Use_::class;
    }
    /**
     * @param Use_ $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->uses === []) {
            return null;
        }
        $only_use = $node->uses[0];
        return $only_use->name->to_string();
    }
}