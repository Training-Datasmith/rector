<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Reflection_Provider;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * @implements NodeNameResolverInterface<FuncCall>
 */
final class Func_Call_Name_Resolver implements Node_Name_Resolver_Interface
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Reflection_Provider $reflection_provider)
    {
        $this->reflection_provider = $reflection_provider;
    }
    public function get_node(): string
    {
        return Func_Call::class;
    }
    /**
     * If some function is namespaced, it will be used over global one.
     * But only if it really exists.
     *
     * @param FuncCall $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->name instanceof Expr) {
            return null;
        }
        $namespace_name = $node->name->get_attribute(Attribute_Key::NAMESPACED_NAME);
        if ($namespace_name instanceof Fully_Qualified) {
            $function_fqn_name = $namespace_name->to_string();
            if ($this->reflection_provider->has_function($namespace_name, null)) {
                return $function_fqn_name;
            }
        }
        if (is_string($namespace_name)) {
            return $namespace_name;
        }
        return (string) $node->name;
    }
}