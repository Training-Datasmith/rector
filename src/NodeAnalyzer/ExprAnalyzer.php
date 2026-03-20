<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Binary_Op\Boolean_And;
use Php_Parser\Node\Expr\Binary_Op\Boolean_Or;
use Php_Parser\Node\Expr\Binary_Op\Equal;
use Php_Parser\Node\Expr\Binary_Op\Greater;
use Php_Parser\Node\Expr\Binary_Op\Greater_Or_Equal;
use Php_Parser\Node\Expr\Binary_Op\Identical;
use Php_Parser\Node\Expr\Binary_Op\Logical_And;
use Php_Parser\Node\Expr\Binary_Op\Logical_Or;
use Php_Parser\Node\Expr\Binary_Op\Logical_Xor;
use Php_Parser\Node\Expr\Binary_Op\Not_Equal;
use Php_Parser\Node\Expr\Binary_Op\Not_Identical;
use Php_Parser\Node\Expr\Binary_Op\Smaller;
use Php_Parser\Node\Expr\Binary_Op\Smaller_Or_Equal;
use Php_Parser\Node\Expr\Bitwise_Not;
use Php_Parser\Node\Expr\Boolean_Not;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Cast;
use Php_Parser\Node\Expr\Cast\Bool_;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Expr\Clone_;
use Php_Parser\Node\Expr\Const_Fetch;
use Php_Parser\Node\Expr\Empty_;
use Php_Parser\Node\Expr\Error_Suppress;
use Php_Parser\Node\Expr\Eval_;
use Php_Parser\Node\Expr\Exit_;
use Php_Parser\Node\Expr\Include_;
use Php_Parser\Node\Expr\Instanceof_;
use Php_Parser\Node\Expr\Isset_;
use Php_Parser\Node\Expr\Print_;
use Php_Parser\Node\Expr\Throw_;
use Php_Parser\Node\Expr\Unary_Minus;
use Php_Parser\Node\Expr\Unary_Plus;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Expr\Yield_From;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\Interpolated_String;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Analyser\Scope;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\Union_Type;
use Rector\Enum\Object_Reference;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Expr_Analyzer
{
    public function is_bool_expr(Expr $expr): bool
    {
        return $expr instanceof Boolean_Not || $expr instanceof Empty_ || $expr instanceof Isset_ || $expr instanceof Instanceof_ || $expr instanceof Bool_ || $expr instanceof Equal || $expr instanceof Not_Equal || $expr instanceof Identical || $expr instanceof Not_Identical || $expr instanceof Greater || $expr instanceof Greater_Or_Equal || $expr instanceof Smaller || $expr instanceof Smaller_Or_Equal || $expr instanceof Boolean_And || $expr instanceof Boolean_Or || $expr instanceof Logical_And || $expr instanceof Logical_Or || $expr instanceof Logical_Xor;
    }
    public function is_call_like_return_native_bool(Expr $expr): bool
    {
        if (!$expr instanceof Call_Like) {
            return \false;
        }
        $scope = $expr->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return \false;
        }
        $native_type = $scope->get_native_type($expr);
        return $native_type->is_boolean()->yes();
    }
    /**
     * Verify that Expr has ->expr property that can be wrapped by parentheses
     */
    public function is_expr_with_expr_property_wrappable(Node $node): bool
    {
        if (!$node instanceof Expr) {
            return \false;
        }
        // ensure only verify on reprint, using token start verification is more reliable for its check
        if ($node->get_start_token_pos() > 0) {
            return \false;
        }
        if ($node instanceof Cast || $node instanceof Yield_From || $node instanceof Unary_Minus || $node instanceof Unary_Plus || $node instanceof Throw_ || $node instanceof Empty_ || $node instanceof Boolean_Not || $node instanceof Clone_ || $node instanceof Error_Suppress || $node instanceof Bitwise_Not || $node instanceof Eval_ || $node instanceof Print_ || $node instanceof Exit_ || $node instanceof Include_ || $node instanceof Instanceof_) {
            return $node->expr instanceof Binary_Op;
        }
        return \false;
    }
    public function is_non_typed_from_param(Expr $expr): bool
    {
        if (!$expr instanceof Variable) {
            return \false;
        }
        $scope = $expr->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            // uncertainty when scope not yet filled/overlapped on just refactored
            return \true;
        }
        $native_type = $scope->get_native_type($expr);
        $type = $scope->get_type($expr);
        if ($native_type instanceof Mixed_Type && !$native_type->is_explicit_mixed() || $native_type instanceof Mixed_Type && !$type instanceof Mixed_Type) {
            return \true;
        }
        if ($native_type instanceof Object_Without_Class_Type && !$type instanceof Object_Without_Class_Type) {
            return \true;
        }
        if ($native_type instanceof Union_Type) {
            return !$native_type->equals($type);
        }
        return !$native_type->is_super_type_of($type)->yes();
    }
    public function is_dynamic_expr(Expr $expr): bool
    {
        // Unwrap UnaryPlus and UnaryMinus
        if ($expr instanceof Unary_Plus || $expr instanceof Unary_Minus) {
            $expr = $expr->expr;
        }
        if ($expr instanceof Array_) {
            return $this->is_dynamic_array($expr);
        }
        if ($expr instanceof Scalar) {
            // string interpolation is true, otherwise false
            return $expr instanceof Interpolated_String;
        }
        return !$this->is_allowed_const_fetch_or_class_const_fetch($expr);
    }
    public function is_dynamic_array(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if (!$item instanceof Array_Item) {
                continue;
            }
            if (!$this->is_allowed_array_key($item->key)) {
                return \true;
            }
            if (!$this->is_allowed_array_value($item->value)) {
                return \true;
            }
        }
        return \false;
    }
    private function is_allowed_const_fetch_or_class_const_fetch(Expr $expr): bool
    {
        if ($expr instanceof Const_Fetch) {
            return \true;
        }
        if ($expr instanceof Class_Const_Fetch) {
            if (!$expr->class instanceof Name) {
                return \false;
            }
            if (!$expr->name instanceof Identifier) {
                return \false;
            }
            // static::class cannot be used for compile-time class name resolution
            return $expr->class->to_string() !== Object_Reference::STATIC;
        }
        return \false;
    }
    private function is_allowed_array_key(?Expr $expr): bool
    {
        if (!$expr instanceof Expr) {
            return \true;
        }
        if ($expr instanceof String_) {
            return \true;
        }
        return $expr instanceof Int_;
    }
    private function is_allowed_array_value(Expr $expr): bool
    {
        if ($expr instanceof Array_) {
            return !$this->is_dynamic_array($expr);
        }
        return !$this->is_dynamic_expr($expr);
    }
}