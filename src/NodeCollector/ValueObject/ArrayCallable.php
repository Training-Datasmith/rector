<?php

declare (strict_types=1);
namespace Rector\Node_Collector\Value_Object;

use Php_Parser\Node\Expr;
use Rector\Validation\Rector_Assert;
final class Array_Callable
{
    /**
     * @readonly
     */
    private Expr $caller_expr;
    /**
     * @readonly
     */
    private string $class;
    /**
     * @readonly
     */
    private string $method;
    public function __construct(Expr $caller_expr, string $class, string $method)
    {
        $this->caller_expr = $caller_expr;
        $this->class = $class;
        $this->method = $method;
        Rector_Assert::class_name($class);
    }
    public function get_class(): string
    {
        return $this->class;
    }
    public function get_method(): string
    {
        return $this->method;
    }
    public function get_caller_expr(): Expr
    {
        return $this->caller_expr;
    }
}