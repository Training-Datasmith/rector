<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Const_Fetch;
/**
 * Read-only utils for ClassConstAnalyzer Node:
 * "false, true..."
 */
final class Const_Fetch_Analyzer
{
    public function is_true_or_false(Expr $expr): bool
    {
        if ($this->is_true($expr)) {
            return \true;
        }
        return $this->is_false($expr);
    }
    public function is_false(Expr $expr): bool
    {
        return $this->is_constant_with_lowercased_name($expr, 'false');
    }
    public function is_true(Expr $expr): bool
    {
        return $this->is_constant_with_lowercased_name($expr, 'true');
    }
    public function is_null(Expr $expr): bool
    {
        return $this->is_constant_with_lowercased_name($expr, 'null');
    }
    private function is_constant_with_lowercased_name(Node $node, string $name): bool
    {
        if (!$node instanceof Const_Fetch) {
            return \false;
        }
        return $node->name->to_lower_string() === $name;
    }
}