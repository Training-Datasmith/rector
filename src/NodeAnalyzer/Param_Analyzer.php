<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Expr\Error;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Nullable_Type;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node_Visitor;
use Rector\Node_Manipulator\Func_Call_Manipulator;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Comparing\Node_Comparator;
use Rector\Php_Parser\Node\Better_Node_Finder;
final class Param_Analyzer
{
    /**
     * @readonly
     */
    private Node_Comparator $node_comparator;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Func_Call_Manipulator $func_call_manipulator;
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @var string[]
     */
    private const VARIADIC_FUNCTION_NAMES = ['func_get_arg', 'func_get_args', 'func_num_args', 'get_defined_vars'];
    public function __construct(Node_Comparator $node_comparator, Node_Name_Resolver $node_name_resolver, Func_Call_Manipulator $func_call_manipulator, Simple_Callable_Node_Traverser $simple_callable_node_traverser, Better_Node_Finder $better_node_finder)
    {
        $this->node_comparator = $node_comparator;
        $this->node_name_resolver = $node_name_resolver;
        $this->func_call_manipulator = $func_call_manipulator;
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
        $this->better_node_finder = $better_node_finder;
    }
    public function is_param_used_in_class_method(Class_Method $class_method, Param $param): bool
    {
        if ($param->is_promoted()) {
            return \true;
        }
        $is_param_used = \false;
        if ($param->var instanceof Error) {
            return \false;
        }
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($class_method->stmts, function (Node $node) use (&$is_param_used, $param): ?int {
            if ($this->is_variadic_func_call($node)) {
                $is_param_used = \true;
                return Node_Visitor::STOP_TRAVERSAL;
            }
            if ($this->is_used_as_arg($node, $param)) {
                $is_param_used = \true;
                return Node_Visitor::STOP_TRAVERSAL;
            }
            // skip nested anonymous class
            if ($node instanceof Class_ || $node instanceof Function_) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($node instanceof Variable && $this->node_comparator->are_nodes_equal($node, $param->var)) {
                $is_param_used = \true;
                return Node_Visitor::STOP_TRAVERSAL;
            }
            if ($node instanceof Closure && $this->is_variable_in_closure_uses($node, $param->var)) {
                $is_param_used = \true;
                return Node_Visitor::STOP_TRAVERSAL;
            }
            if ($this->is_param_used($node, $param)) {
                $is_param_used = \true;
                return Node_Visitor::STOP_TRAVERSAL;
            }
            return null;
        });
        return $is_param_used;
    }
    /**
     * @param Param[] $params
     */
    public function has_property_promotion(array $params): bool
    {
        foreach ($params as $param) {
            if ($param->is_promoted()) {
                return \true;
            }
        }
        return \false;
    }
    public function is_nullable(Param $param): bool
    {
        if ($param->variadic) {
            return \false;
        }
        if (!$param->type instanceof Node) {
            return \false;
        }
        return $param->type instanceof Nullable_Type;
    }
    public function is_param_reassign(Class_Method $class_method, Param $param): bool
    {
        $param_name = $this->node_name_resolver->get_name($param);
        return (bool) $this->better_node_finder->find_first_in_function_like_scoped($class_method, function (Node $node) use ($param_name): bool {
            if (!$node instanceof Assign) {
                return \false;
            }
            if (!$node->var instanceof Variable) {
                return \false;
            }
            return $this->node_name_resolver->is_name($node->var, $param_name);
        });
    }
    private function is_variable_in_closure_uses(Closure $closure, Variable $variable): bool
    {
        foreach ($closure->uses as $use) {
            if ($this->node_comparator->are_nodes_equal($use->var, $variable)) {
                return \true;
            }
        }
        return \false;
    }
    private function is_used_as_arg(Node $node, Param $param): bool
    {
        if ($node instanceof New_ || $node instanceof Call_Like) {
            if ($node->is_first_class_callable()) {
                return \false;
            }
            foreach ($node->get_args() as $arg) {
                if ($this->node_comparator->are_nodes_equal($param->var, $arg->value)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function is_param_used(Node $node, Param $param): bool
    {
        if (!$node instanceof Func_Call) {
            return \false;
        }
        if (!$this->node_name_resolver->is_name($node, 'compact')) {
            return \false;
        }
        $arguments = $this->func_call_manipulator->extract_arguments_from_compact_func_calls([$node]);
        return $this->node_name_resolver->is_names($param, $arguments);
    }
    private function is_variadic_func_call(Node $node): bool
    {
        if (!$node instanceof Func_Call) {
            return \false;
        }
        return $this->node_name_resolver->is_names($node, self::VARIADIC_FUNCTION_NAMES);
    }
}