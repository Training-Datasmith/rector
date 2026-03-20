<?php

declare (strict_types=1);
namespace Rector\Value_Object;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Func_Call;
final class Func_Call_And_Expr
{
    /**
     * @readonly
     */
    private Func_Call $func_call;
    /**
     * @readonly
     */
    private Expr $expr;
    public function __construct(Func_Call $func_call, Expr $expr)
    {
        $this->func_call = $func_call;
        $this->expr = $expr;
    }
    public function get_func_call(): Func_Call
    {
        return $this->func_call;
    }
    public function get_expr(): Expr
    {
        return $this->expr;
    }
}