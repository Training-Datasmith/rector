<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Closure;
use Php_Stan\Reflection\Extended_Parameter_Reflection;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Php_Stan\Parameters_Acceptor_Selector_Variants_Wrapper;
use Rector\Reflection\Reflection_Resolver;
final class Call_Like_Expects_This_Bound_Closure_Args_Analyzer
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
     * @return Arg[]
     */
    public function get_args_using_this_bound_closure(Call_Like $call_like): array
    {
        if ($call_like->is_first_class_callable() || $call_like->get_args() === []) {
            return [];
        }
        $call_args = $call_like->get_args();
        $has_closure_arg = (bool) array_filter($call_args, fn(Arg $arg): bool => $arg->value instanceof Closure);
        if (!$has_closure_arg) {
            return [];
        }
        $args_using_this_bound_closure = [];
        $reflection = $this->reflection_resolver->resolve_function_like_reflection_from_call($call_like);
        if ($reflection === null) {
            return [];
        }
        $scope = $call_like->get_attribute(Attribute_Key::SCOPE);
        if ($scope === null) {
            return [];
        }
        $parameters_acceptor = Parameters_Acceptor_Selector_Variants_Wrapper::select($reflection, $call_like, $scope);
        $parameters = $parameters_acceptor->get_parameters();
        foreach ($call_args as $index => $arg) {
            if (!$arg->value instanceof Closure) {
                continue;
            }
            if ((($nullsafe_variable1 = $arg->name) ? $nullsafe_variable1->name : null) !== null) {
                foreach ($parameters as $parameter) {
                    if (!$parameter instanceof Extended_Parameter_Reflection) {
                        continue;
                    }
                    $has_object_binding = (bool) $parameter->get_closure_this_type();
                    if ($has_object_binding && $arg->name->name === $parameter->get_name()) {
                        $args_using_this_bound_closure[] = $arg;
                    }
                }
                continue;
            }
            if (!is_string(($nullsafe_variable2 = $arg->name) ? $nullsafe_variable2->name : null)) {
                $parameter = $parameters[$index] ?? null;
                if (!$parameter instanceof Extended_Parameter_Reflection) {
                    continue;
                }
                $has_object_binding = (bool) $parameter->get_closure_this_type();
                if ($has_object_binding) {
                    $args_using_this_bound_closure[] = $arg;
                }
            }
        }
        return $args_using_this_bound_closure;
    }
}