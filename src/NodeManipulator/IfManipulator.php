<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Binary_Op\Boolean_Or;
use Php_Parser\Node\Expr\Binary_Op\Not_Identical;
use Php_Parser\Node\Expr\Exit_;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\Foreach_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node\Stmt\Return_;
use Rector\Php_Parser\Comparing\Node_Comparator;
use Rector\Php_Parser\Node\Better_Node_Finder;
use Rector\Php_Parser\Node\Value\Value_Resolver;
final class If_Manipulator
{
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @readonly
     */
    private \Rector\Node_Manipulator\Stmts_Manipulator $stmts_manipulator;
    /**
     * @readonly
     */
    private Value_Resolver $value_resolver;
    /**
     * @readonly
     */
    private Node_Comparator $node_comparator;
    public function __construct(Better_Node_Finder $better_node_finder, \Rector\Node_Manipulator\Stmts_Manipulator $stmts_manipulator, Value_Resolver $value_resolver, Node_Comparator $node_comparator)
    {
        $this->better_node_finder = $better_node_finder;
        $this->stmts_manipulator = $stmts_manipulator;
        $this->value_resolver = $value_resolver;
        $this->node_comparator = $node_comparator;
    }
    /**
     * Matches:
     *
     * if (<$value> !== null) {
     *     return $value;
     * }
     */
    public function match_if_not_null_return_value(If_ $if): ?Expr
    {
        if (count($if->stmts) !== 1) {
            return null;
        }
        $inside_if_node = $if->stmts[0];
        if (!$inside_if_node instanceof Return_) {
            return null;
        }
        if (!$if->cond instanceof Not_Identical) {
            return null;
        }
        return $this->match_compared_and_returned_node($if->cond, $inside_if_node);
    }
    /**
     * @return If_[]
     */
    public function collect_nested_ifs_with_only_return(If_ $if): array
    {
        $ifs = [];
        $current_if = $if;
        while ($this->is_if_with_only_stmt_if($current_if)) {
            $ifs[] = $current_if;
            /** @var If_ $currentIf */
            $current_if = $current_if->stmts[0];
        }
        if ($ifs === []) {
            return [];
        }
        if (!$this->has_only_stmt_of_type($current_if, Return_::class)) {
            return [];
        }
        // last if is with the return value
        $ifs[] = $current_if;
        return $ifs;
    }
    public function is_if_and_else_with_same_variable_assign_as_last_stmts(If_ $if, Expr $desired_expr): bool
    {
        if (!$if->else instanceof Else_) {
            return \false;
        }
        if ((bool) $if->elseifs) {
            return \false;
        }
        $last_if_node = $this->stmts_manipulator->get_unwrapped_last_stmt($if->stmts);
        if (!$last_if_node instanceof Assign) {
            return \false;
        }
        $last_else_node = $this->stmts_manipulator->get_unwrapped_last_stmt($if->else->stmts);
        if (!$last_else_node instanceof Assign) {
            return \false;
        }
        if (!$last_if_node->var instanceof Variable) {
            return \false;
        }
        if (!$this->node_comparator->are_nodes_equal($last_if_node->var, $last_else_node->var)) {
            return \false;
        }
        return $this->node_comparator->are_nodes_equal($desired_expr, $last_else_node->var);
    }
    /**
     * @return If_[]
     */
    public function collect_nested_ifs_with_non_breaking(Foreach_ $foreach): array
    {
        if (count($foreach->stmts) !== 1) {
            return [];
        }
        $only_foreach_stmt = $foreach->stmts[0];
        if (!$only_foreach_stmt instanceof If_) {
            return [];
        }
        if ($only_foreach_stmt->cond instanceof Boolean_Or) {
            return [];
        }
        $ifs = [];
        $current_if = $only_foreach_stmt;
        while ($this->is_if_with_only_stmt_if($current_if)) {
            $ifs[] = $current_if;
            /** @var If_ $currentIf */
            $current_if = $current_if->stmts[0];
        }
        // IfManipulator is not build to handle elseif and else
        if (!$this->is_if_without_else_and_else_ifs($current_if)) {
            return [];
        }
        if ($this->better_node_finder->has_instances_of($current_if->stmts, [Return_::class, Exit_::class])) {
            return [];
        }
        // last if is with the expression
        $ifs[] = $current_if;
        return $ifs;
    }
    /**
     * @param class-string<Stmt> $stmtClass
     */
    public function is_if_with_only(If_ $if, string $stmt_class): bool
    {
        if (!$this->is_if_without_else_and_else_ifs($if)) {
            return \false;
        }
        return $this->has_only_stmt_of_type($if, $stmt_class);
    }
    public function is_if_without_else_and_else_ifs(If_ $if): bool
    {
        if ($if->else instanceof Else_) {
            return \false;
        }
        return $if->elseifs === [];
    }
    private function match_compared_and_returned_node(Not_Identical $not_identical, Return_ $return): ?Expr
    {
        if ($this->node_comparator->are_nodes_equal($not_identical->left, $return->expr) && $this->value_resolver->is_null($not_identical->right)) {
            return $not_identical->left;
        }
        if (!$this->node_comparator->are_nodes_equal($not_identical->right, $return->expr)) {
            return null;
        }
        if ($this->value_resolver->is_null($not_identical->left)) {
            return $not_identical->right;
        }
        return null;
    }
    private function is_if_with_only_stmt_if(If_ $if): bool
    {
        if (!$this->is_if_without_else_and_else_ifs($if)) {
            return \false;
        }
        return $this->has_only_stmt_of_type($if, If_::class);
    }
    /**
     * @param class-string<Stmt> $stmtClass
     */
    private function has_only_stmt_of_type(If_ $if, string $stmt_class): bool
    {
        $stmts = $if->stmts;
        if (count($stmts) !== 1) {
            return \false;
        }
        return $stmts[0] instanceof $stmt_class;
    }
}