<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Function_;
use Php_Stan\Analyser\Scope;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
/**
 * @implements NodeNameResolverInterface<Function_>
 */
final class Function_Name_Resolver implements Node_Name_Resolver_Interface
{
    public function get_node(): string
    {
        return Function_::class;
    }
    /**
     * @param Function_ $node
     */
    public function resolve(Node $node, ?Scope $scope): string
    {
        $bare_name = (string) $node->name;
        if (!$scope instanceof Scope) {
            return $bare_name;
        }
        $namespace_name = $scope->get_namespace();
        if ($namespace_name !== null) {
            return $namespace_name . '\\' . $bare_name;
        }
        return $bare_name;
    }
}