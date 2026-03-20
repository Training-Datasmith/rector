<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Node_Factory;

use Php_Parser\Builder_Helpers;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Scalar\String_;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Named_Args_Factory
{
    /**
     * @param array<string|int, mixed|Expr> $values
     * @return list<Arg>
     */
    public function create_from_values(array $values): array
    {
        $args = [];
        foreach ($values as $key => $arg_value) {
            $name = null;
            if ($arg_value instanceof Array_Item) {
                if ($arg_value->key instanceof String_) {
                    $name = new Identifier($arg_value->key->value);
                }
                $arg_value = $arg_value->value;
            }
            $expr = Builder_Helpers::normalize_value($arg_value);
            // for named arguments
            if (!$name instanceof Identifier && is_string($key)) {
                $name = new Identifier($key);
            }
            $this->normalize_string_double_quote($expr);
            $args[] = new Arg($expr, \false, \false, [], $name);
        }
        return $args;
    }
    private function normalize_string_double_quote(Expr $expr): void
    {
        if (!$expr instanceof String_) {
            return;
        }
        // avoid escaping quotes + preserve newlines
        if (strpos($expr->value, "'") === \false) {
            return;
        }
        if (strpos($expr->value, "\n") !== \false) {
            return;
        }
        $expr->set_attribute(Attribute_Key::KIND, String_::KIND_DOUBLE_QUOTED);
    }
}