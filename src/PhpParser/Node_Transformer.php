<?php

declare (strict_types=1);
namespace Rector\Php_Parser;

use Php_Parser\Builder_Helpers;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Binary_Op\Concat;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Yield_;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Expression;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Util\String_Utils;
use Rector\Value_Object\Sprintf_String_And_Args;
/**
 * @api used in phpunit
 */
final class Node_Transformer
{
    /**
     * @see https://regex101.com/r/XFc3qA/1
     * @var string
     */
    private const PERCENT_TEXT_REGEX = '#^%\w$#';
    /**
     * @api used in phpunit symfony
     *
     * From:
     * - sprintf("Hi %s", $name);
     *
     * to:
     * - ["Hi %s", $name]
     */
    public function transform_sprintf_to_array(Func_Call $sprintf_func_call): ?Array_
    {
        $sprintf_string_and_args = $this->split_message_and_args($sprintf_func_call);
        if (!$sprintf_string_and_args instanceof Sprintf_String_And_Args) {
            return null;
        }
        $array_items = $sprintf_string_and_args->get_array_items();
        $string_value = $sprintf_string_and_args->get_string_value();
        $message_parts = $this->split_by_space($string_value);
        $array_message_parts = [];
        foreach ($message_parts as $message_part) {
            if (String_Utils::is_match($message_part, self::PERCENT_TEXT_REGEX)) {
                /** @var Expr $messagePartNode */
                $message_part_node = array_shift($array_items);
            } else {
                $message_part_node = new String_($message_part);
            }
            $array_message_parts[] = new Array_Item($message_part_node);
        }
        return new Array_($array_message_parts);
    }
    /**
     * @return Expression[]
     */
    public function transform_array_to_yields(Array_ $array): array
    {
        $yields = [];
        foreach ($array->items as $array_item) {
            $yield = new Yield_($array_item->value, $array_item->key);
            $expression = new Expression($yield);
            $array_item_comments = $array_item->get_comments();
            if ($array_item_comments !== []) {
                $expression->set_attribute(Attribute_Key::COMMENTS, $array_item_comments);
            }
            $yields[] = $expression;
        }
        return $yields;
    }
    /**
     * @api symfony
     */
    public function transform_concat_to_string_array(Concat $concat): Array_
    {
        $array_items = $this->transform_concat_to_items($concat);
        $expr = Builder_Helpers::normalize_value($array_items);
        if (!$expr instanceof Array_) {
            throw new Should_Not_Happen_Exception();
        }
        return $expr;
    }
    private function split_message_and_args(Func_Call $sprintf_func_call): ?Sprintf_String_And_Args
    {
        $string_argument = null;
        $array_items = [];
        foreach ($sprintf_func_call->args as $i => $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }
            if ($i === 0) {
                $string_argument = $arg->value;
            } else {
                $array_items[] = $arg->value;
            }
        }
        if (!$string_argument instanceof String_) {
            return null;
        }
        if ($array_items === []) {
            return null;
        }
        return new Sprintf_String_And_Args($string_argument, $array_items);
    }
    /**
     * @return string[]
     */
    private function split_by_space(string $value): array
    {
        $value = str_getcsv($value, ' ', '"', '\\');
        return array_filter($value);
    }
    /**
     * @return mixed[]
     */
    private function transform_concat_to_items(Concat $concat): array
    {
        $array_items = $this->transform_concat_item_to_array_items($concat->left);
        return array_merge($array_items, $this->transform_concat_item_to_array_items($concat->right));
    }
    /**
     * @return mixed[]|Expr[]|String_[]
     */
    private function transform_concat_item_to_array_items(Expr $expr): array
    {
        if ($expr instanceof Concat) {
            return $this->transform_concat_to_items($expr);
        }
        if (!$expr instanceof String_) {
            return [$expr];
        }
        $array_items = [];
        $parts = $this->split_by_space($expr->value);
        foreach ($parts as $part) {
            if (trim($part) !== '') {
                $array_items[] = new String_($part);
            }
        }
        return $array_items;
    }
}