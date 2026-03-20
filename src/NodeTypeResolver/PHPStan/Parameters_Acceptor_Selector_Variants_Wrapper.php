<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan;

use Php_Parser\Node\Function_Like;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Parameters_Acceptor;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
final class Parameters_Acceptor_Selector_Variants_Wrapper
{
    /**
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection $reflection
     * @param \PhpParser\Node\Expr\CallLike|\PhpParser\Node\FunctionLike $node
     */
    public static function select($reflection, $node, Scope $scope): Parameters_Acceptor
    {
        $variants = $reflection->get_variants();
        if ($node instanceof Function_Like) {
            return Parameters_Acceptor_Selector::combine_acceptors($variants);
        }
        if ($node->is_first_class_callable()) {
            return Parameters_Acceptor_Selector::combine_acceptors($variants);
        }
        return Parameters_Acceptor_Selector::select_from_args($scope, $node->get_args(), $variants);
    }
}