<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Func_Call;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Value_Object\Func_Call_And_Expr;
final class Binary_Op_Analyzer
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver)
    {
        $this->node_name_resolver = $node_name_resolver;
    }
    public function match_func_call_and_other_expr(Binary_Op $binary_op, string $func_call_name): ?Func_Call_And_Expr
    {
        if ($binary_op->left instanceof Func_Call) {
            if (!$this->node_name_resolver->is_name($binary_op->left, $func_call_name)) {
                return null;
            }
            return new Func_Call_And_Expr($binary_op->left, $binary_op->right);
        }
        if ($binary_op->right instanceof Func_Call) {
            if (!$this->node_name_resolver->is_name($binary_op->right, $func_call_name)) {
                return null;
            }
            return new Func_Call_And_Expr($binary_op->right, $binary_op->left);
        }
        return null;
    }
}