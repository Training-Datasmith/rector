<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Rector\Reflection\Reflection_Resolver;
final class Variadic_Analyzer
{
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    public function __construct(Reflection_Resolver $reflection_resolver)
    {
        $this->reflection_resolver = $reflection_resolver;
    }
    /**
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\MethodCall $call
     */
    public function has_variadic_parameters(\Php_Parser\Node\Expr\Call_Like $call): bool
    {
        $function_like_reflection = $this->reflection_resolver->resolve_function_like_reflection_from_call($call);
        if ($function_like_reflection === null) {
            return \false;
        }
        return $this->has_variadic_variant($function_like_reflection);
    }
    /**
     * @param \PHPStan\Reflection\MethodReflection|\PHPStan\Reflection\FunctionReflection $functionLikeReflection
     */
    private function has_variadic_variant($function_like_reflection): bool
    {
        foreach ($function_like_reflection->get_variants() as $parameters_acceptor) {
            // can be any number of arguments → nothing to limit here
            if ($parameters_acceptor->is_variadic()) {
                return \true;
            }
        }
        return \false;
    }
}