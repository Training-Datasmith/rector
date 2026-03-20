<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Binary_Op\Boolean_And;
use Php_Parser\Node\Expr\Binary_Op\Boolean_Or;
use Php_Parser\Node\Expr\Boolean_Not;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php71\Value_Object\Two_Node_Match;
use Rector\Php_Parser\Node\Assign_And_Binary_Map;
final class Binary_Op_Manipulator
{
    /**
     * @readonly
     */
    private Assign_And_Binary_Map $assign_and_binary_map;
    public function __construct(Assign_And_Binary_Map $assign_and_binary_map)
    {
        $this->assign_and_binary_map = $assign_and_binary_map;
    }
    /**
     * Tries to match left or right parts (xor),
     * returns null or match on first condition and then second condition. No matter what the origin order is.
     *
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $firstCondition
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $secondCondition
     */
    public function match_first_and_second_condition_node(Binary_Op $binary_op, $first_condition, $second_condition): ?Two_Node_Match
    {
        $this->validate_condition($first_condition);
        $this->validate_condition($second_condition);
        $first_condition = $this->normalize_condition($first_condition);
        $second_condition = $this->normalize_condition($second_condition);
        if ($first_condition($binary_op->left, $binary_op->right) && $second_condition($binary_op->right, $binary_op->left)) {
            return new Two_Node_Match($binary_op->left, $binary_op->right);
        }
        if (!$first_condition($binary_op->right, $binary_op->left)) {
            return null;
        }
        if (!$second_condition($binary_op->left, $binary_op->right)) {
            return null;
        }
        return new Two_Node_Match($binary_op->right, $binary_op->left);
    }
    public function inverse_boolean_or(Boolean_Or $boolean_or): ?Binary_Op
    {
        // no nesting
        if ($boolean_or->left instanceof Boolean_Or) {
            return null;
        }
        if ($boolean_or->right instanceof Boolean_Or) {
            return null;
        }
        $inversed_node_class = $this->resolve_inversed_node_class($boolean_or);
        if ($inversed_node_class === null) {
            return null;
        }
        $first_inversed_expr = $this->inverse_node($boolean_or->left);
        $second_inversed_expr = $this->inverse_node($boolean_or->right);
        return new $inversed_node_class($first_inversed_expr, $second_inversed_expr);
    }
    public function invert_condition(Binary_Op $binary_op): ?Binary_Op
    {
        // no nesting
        if ($binary_op->left instanceof Boolean_Or) {
            return null;
        }
        if ($binary_op->right instanceof Boolean_Or) {
            return null;
        }
        $inversed_node_class = $this->resolve_inversed_node_class($binary_op);
        if ($inversed_node_class === null) {
            return null;
        }
        return new $inversed_node_class($binary_op->left, $binary_op->right);
    }
    /**
     * @return \PhpParser\Node\Expr\BinaryOp|\PhpParser\Node\Expr|\PhpParser\Node\Expr\BooleanNot
     */
    public function inverse_node(Expr $expr)
    {
        if ($expr instanceof Binary_Op) {
            $inversed_binary_op = $this->assign_and_binary_map->get_inversed($expr);
            if ($inversed_binary_op !== null) {
                return new $inversed_binary_op($expr->left, $expr->right);
            }
        }
        if ($expr instanceof Boolean_Not) {
            return $expr->expr;
        }
        return new Boolean_Not($expr);
    }
    /**
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $firstCondition
     */
    private function validate_condition($first_condition): void
    {
        if (is_callable($first_condition)) {
            return;
        }
        if (is_a($first_condition, Node::class, \true)) {
            return;
        }
        throw new Should_Not_Happen_Exception();
    }
    /**
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $condition
     * @return callable(Node $firstNode, Node $secondNode): bool
     */
    private function normalize_condition($condition): callable
    {
        if (is_callable($condition)) {
            return $condition;
        }
        return static fn(Node $node): bool => $node instanceof $condition;
    }
    /**
     * @return class-string<BinaryOp>|null
     */
    private function resolve_inversed_node_class(Binary_Op $binary_op): ?string
    {
        $inversed_node_class = $this->assign_and_binary_map->get_inversed($binary_op);
        if ($inversed_node_class !== null) {
            return $inversed_node_class;
        }
        if ($binary_op instanceof Boolean_Or) {
            return Boolean_And::class;
        }
        return null;
    }
}