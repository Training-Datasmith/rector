<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Variadic_Placeholder;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
final class Compact_Func_Call_Analyzer
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver)
    {
        $this->node_name_resolver = $node_name_resolver;
    }
    public function is_in_compact(Func_Call $func_call, Variable $variable): bool
    {
        if (!$this->node_name_resolver->is_name($func_call, 'compact')) {
            return \false;
        }
        if (!is_string($variable->name)) {
            return \false;
        }
        return $this->is_in_arg_or_array_item_nodes($func_call->args, $variable->name);
    }
    /**
     * @param array<int, Arg|VariadicPlaceholder|ArrayItem|null> $nodes
     */
    private function is_in_arg_or_array_item_nodes(array $nodes, string $variable_name): bool
    {
        foreach ($nodes as $node) {
            if ($this->should_skip($node)) {
                continue;
            }
            /** @var Arg|ArrayItem $node */
            if ($node->value instanceof Array_) {
                if ($this->is_in_arg_or_array_item_nodes($node->value->items, $variable_name)) {
                    return \true;
                }
                continue;
            }
            if (!$node->value instanceof String_) {
                continue;
            }
            if ($node->value->value === $variable_name) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder|\PhpParser\Node\ArrayItem|null $node
     */
    private function should_skip($node): bool
    {
        if ($node === null) {
            return \true;
        }
        return $node instanceof Variadic_Placeholder;
    }
}