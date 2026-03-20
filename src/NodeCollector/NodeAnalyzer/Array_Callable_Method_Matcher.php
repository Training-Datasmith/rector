<?php

declare (strict_types=1);
namespace Rector\Node_Collector\Node_Analyzer;

use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Scalar\Magic_Const\Class_;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\This_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_With_Class_Name;
use Rector\Enum\Object_Reference;
use Rector\Node_Collector\Value_Object\Array_Callable;
use Rector\Node_Collector\Value_Object\Array_Callable_Dynamic_Method;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Php_Parser\Node\Value\Value_Resolver;
use Rector\Reflection\Reflection_Resolver;
use Rector\Value_Object\Method_Name;
final class Array_Callable_Method_Matcher
{
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @readonly
     */
    private Value_Resolver $value_resolver;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    public function __construct(Node_Type_Resolver $node_type_resolver, Value_Resolver $value_resolver, Reflection_Provider $reflection_provider, Reflection_Resolver $reflection_resolver)
    {
        $this->node_type_resolver = $node_type_resolver;
        $this->value_resolver = $value_resolver;
        $this->reflection_provider = $reflection_provider;
        $this->reflection_resolver = $reflection_resolver;
    }
    /**
     * Matches array like: "[$this, 'methodName']" → ['ClassName', 'methodName']
     * Returns ArrayCallableDynamicMethod object when unknown method of callable used, eg: [$this, $other]
     * @see https://github.com/rectorphp/rector-src/pull/908
     * @see https://github.com/rectorphp/rector-src/pull/909
     * @return null|\Rector\NodeCollector\ValueObject\ArrayCallableDynamicMethod|\Rector\NodeCollector\ValueObject\ArrayCallable
     */
    public function match(Array_ $array, Scope $scope, ?string $class_method_name = null)
    {
        if (count($array->items) !== 2) {
            return null;
        }
        if ($this->should_skip_null_items($array)) {
            return null;
        }
        /** @var ArrayItem[] $items */
        $items = $array->items;
        // $this, self, static, FQN
        $first_item_value = $items[0]->value;
        $caller_type = $this->resolve_caller_type($first_item_value, $scope, $class_method_name);
        if (!$caller_type instanceof Type_With_Class_Name) {
            return null;
        }
        if ($array->get_attribute(Attribute_Key::IS_ARRAY_IN_ATTRIBUTE) === \true) {
            return null;
        }
        $values = $this->value_resolver->get_value($array);
        $class_name = $caller_type->get_class_name();
        $second_item_value = $items[1]->value;
        if ($values === null) {
            return new Array_Callable_Dynamic_Method();
        }
        if ($this->should_skip_associative_array($values)) {
            return null;
        }
        if (!$second_item_value instanceof String_) {
            return null;
        }
        if ($this->is_callback_at_function_names($array, ['register_shutdown_function', 'forward_static_call'])) {
            return null;
        }
        $method_name = $second_item_value->value;
        if ($method_name === Method_Name::CONSTRUCT) {
            return null;
        }
        // skip non-existing methods
        if (!$caller_type->has_method($method_name)->yes()) {
            return null;
        }
        return new Array_Callable($first_item_value, $class_name, $method_name);
    }
    private function should_skip_null_items(Array_ $array): bool
    {
        if (!$array->items[0] instanceof Array_Item) {
            return \true;
        }
        return !$array->items[1] instanceof Array_Item;
    }
    /**
     * @param mixed $values
     */
    private function should_skip_associative_array($values): bool
    {
        if (!is_array($values)) {
            return \false;
        }
        $keys = array_keys($values);
        return $keys !== [0, 1] && $keys !== [1];
    }
    /**
     * @param string[] $functionNames
     */
    private function is_callback_at_function_names(Array_ $array, array $function_names): bool
    {
        $from_func_call_name = $array->get_attribute(Attribute_Key::FROM_FUNC_CALL_NAME);
        if ($from_func_call_name === null) {
            return \false;
        }
        return in_array($from_func_call_name, $function_names, \true);
    }
    /**
     * @param \PhpParser\Node\Expr\ClassConstFetch|\PhpParser\Node\Scalar\MagicConst\Class_ $classContext
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\ObjectType
     */
    private function resolve_class_context_type($class_context, Scope $scope, ?string $class_method_name)
    {
        $class_constant_reference = $this->value_resolver->get_value($class_context);
        // non-class value
        if (!is_string($class_constant_reference)) {
            return new Mixed_Type();
        }
        if ($this->is_required_class_reflection_resolution($class_constant_reference)) {
            $class_reflection = $this->reflection_resolver->resolve_class_reflection($class_context);
            if (!$class_reflection instanceof Class_Reflection || !$class_reflection->is_class()) {
                return new Mixed_Type();
            }
            $class_constant_reference = $class_reflection->get_name();
        }
        if (!$this->reflection_provider->has_class($class_constant_reference)) {
            return new Mixed_Type();
        }
        $class_reflection = $this->reflection_provider->get_class($class_constant_reference);
        $has_construct = $class_reflection->has_method(Method_Name::CONSTRUCT);
        if (!$has_construct) {
            return new Object_Type($class_constant_reference, null, $class_reflection);
        }
        if (is_string($class_method_name) && $class_reflection->has_native_method($class_method_name)) {
            return new Object_Type($class_constant_reference, null, $class_reflection);
        }
        $extended_method_reflection = $class_reflection->get_method(Method_Name::CONSTRUCT, $scope);
        $extended_parameters_acceptor = Parameters_Acceptor_Selector::combine_acceptors($extended_method_reflection->get_variants());
        foreach ($extended_parameters_acceptor->get_parameters() as $extended_parameter_reflection) {
            if (!$extended_parameter_reflection->get_default_value() instanceof Type) {
                return new Mixed_Type();
            }
        }
        return new Object_Type($class_constant_reference, null, $class_reflection);
    }
    private function resolve_caller_type(Expr $expr, Scope $scope, ?string $class_method_name): Type
    {
        if ($expr instanceof Class_Const_Fetch || $expr instanceof Class_) {
            // class context means self|static ::class or __CLASS__
            $caller_type = $this->resolve_class_context_type($expr, $scope, $class_method_name);
        } else {
            $caller_type = $this->node_type_resolver->get_type($expr);
        }
        if ($caller_type instanceof This_Type) {
            return $caller_type->get_static_object_type();
        }
        return $caller_type;
    }
    private function is_required_class_reflection_resolution(string $class_constant_reference): bool
    {
        if ($class_constant_reference === Object_Reference::STATIC) {
            return \true;
        }
        return $class_constant_reference === '__CLASS__';
    }
}