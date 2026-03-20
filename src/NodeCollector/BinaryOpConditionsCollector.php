<?php

declare (strict_types=1);
namespace Rector\Node_Collector;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Binary_Op;
/**
 * @see \Rector\Tests\NodeCollector\BinaryOpConditionsCollectorTest
 */
final class Binary_Op_Conditions_Collector
{
    /**
     * Collects operands of a sequence of applications of a given left-associative binary operation.
     *
     * For example, for `a + b + c`, which is parsed as `(Plus (Plus a b) c)`, it will return `[a, b, c]`.
     * Note that parenthesization not matching the associativity (e.g. `a + (b + c)`) will return the parenthesized
     * nodes as standalone operands (`[a, b + c]`) even for associative operations.
     * Similarly, for right-associative operations (e.g. `a ?? b ?? c`), the result produced by
     * the implicit parenthesization (`[a, b ?? c]`) might not match the expectations.
     *
     * @api
     * @param class-string<BinaryOp> $binaryOpClass
     * @return array<int, Expr>
     */
    public function find_conditions(Expr $expr, string $binary_op_class): array
    {
        if (get_class($expr) !== $binary_op_class) {
            // Different binary operators, as well as non-BinaryOp expressions
            // are considered trivial case of a single operand (no operators).
            return [$expr];
        }
        $conditions = [];
        /** @var BinaryOp|Expr $expr */
        while ($expr instanceof Binary_Op) {
            $conditions[] = $expr->right;
            $expr = $expr->left;
            if ($binary_op_class !== get_class($expr)) {
                $conditions[] = $expr;
                break;
            }
        }
        krsort($conditions);
        return $conditions;
    }
}