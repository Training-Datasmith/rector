<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr\Func_Call;
use Rector\Php_Parser\Node\Value\Value_Resolver;
final class Func_Call_Manipulator
{
    /**
     * @readonly
     */
    private Value_Resolver $value_resolver;
    public function __construct(Value_Resolver $value_resolver)
    {
        $this->value_resolver = $value_resolver;
    }
    /**
     * @param FuncCall[] $compactFuncCalls
     * @return string[]
     */
    public function extract_arguments_from_compact_func_calls(array $compact_func_calls): array
    {
        $arguments = [];
        foreach ($compact_func_calls as $compact_func_call) {
            foreach ($compact_func_call->args as $arg) {
                if (!$arg instanceof Arg) {
                    continue;
                }
                $value = $this->value_resolver->get_value($arg->value);
                if ($value === null) {
                    continue;
                }
                $arguments[] = $value;
            }
        }
        return $arguments;
    }
}