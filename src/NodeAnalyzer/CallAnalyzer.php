<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Boolean_Not;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Stmt\If_;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Object_Type;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Call_Analyzer
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @var array<class-string<Expr>>
     */
    private const OBJECT_CALL_TYPES = [Method_Call::class, Nullsafe_Method_Call::class, Static_Call::class];
    public function __construct(Reflection_Provider $reflection_provider)
    {
        $this->reflection_provider = $reflection_provider;
    }
    public function is_object_call(Expr $expr): bool
    {
        if ($expr instanceof Boolean_Not) {
            $expr = $expr->expr;
        }
        if ($expr instanceof Binary_Op) {
            $is_object_call_left = $this->is_object_call($expr->left);
            $is_object_call_right = $this->is_object_call($expr->right);
            return $is_object_call_left || $is_object_call_right;
        }
        foreach (self::OBJECT_CALL_TYPES as $object_call_type) {
            if ($expr instanceof $object_call_type) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param If_[] $ifs
     */
    public function does_if_has_object_call(array $ifs): bool
    {
        foreach ($ifs as $if) {
            if ($this->is_object_call($if->cond)) {
                return \true;
            }
        }
        return \false;
    }
    public function is_new_instance(Variable $variable): bool
    {
        $scope = $variable->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return \false;
        }
        $type = $scope->get_native_type($variable);
        if (!$type instanceof Object_Type) {
            return \false;
        }
        $class_name = $type->get_class_name();
        if (!$this->reflection_provider->has_class($class_name)) {
            return \false;
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        return $class_reflection->get_native_reflection()->is_instantiable();
    }
}