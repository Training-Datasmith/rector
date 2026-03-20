<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Arrow_Function;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node_Visitor_Abstract;
use Php_Stan\Reflection\Native\Native_Function_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Type\Callable_Type;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Reflection\Reflection_Resolver;
/**
 * Decorate method call, function call or static call, that accepts closure that
 * requires multiple args (variadic) - to handle them later in specific rules.
 */
final class Closure_With_Variadic_Parameters_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    public function __construct(Reflection_Resolver $reflection_resolver)
    {
        $this->reflection_resolver = $reflection_resolver;
    }
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Call_Like) {
            return null;
        }
        if ($node->is_first_class_callable()) {
            return null;
        }
        if ($node->get_args() === []) {
            return null;
        }
        $method_reflection = $this->reflection_resolver->resolve_function_like_reflection_from_call($node);
        foreach ($node->get_args() as $arg) {
            if (!$arg->value instanceof Closure && !$arg->value instanceof Arrow_Function) {
                continue;
            }
            if ($method_reflection instanceof Native_Function_Reflection) {
                $parameters_acceptors = Parameters_Acceptor_Selector::combine_acceptors($method_reflection->get_variants());
                foreach ($parameters_acceptors->get_parameters() as $extended_parameter_reflection) {
                    if ($extended_parameter_reflection->get_type() instanceof Callable_Type && $extended_parameter_reflection->get_type()->is_variadic()) {
                        $arg->value->set_attribute(Attribute_Key::HAS_CLOSURE_WITH_VARIADIC_ARGS, \true);
                    }
                }
                return null;
            }
            $arg->value->set_attribute(Attribute_Key::HAS_CLOSURE_WITH_VARIADIC_ARGS, \true);
        }
        return null;
    }
}