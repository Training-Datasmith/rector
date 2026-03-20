<?php

declare (strict_types=1);
namespace Rector\Reflection;

use Php_Parser\Node;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Php\Php_Property_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Benevolent_Union_Type;
use Php_Stan\Type\Type_Combinator;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Analyzer\Class_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type;
use Rector\Value_Object\Method_Name;
final class Reflection_Resolver
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Class_Analyzer $class_analyzer;
    /**
     * @readonly
     */
    private \Rector\Reflection\Method_Reflection_Resolver $method_reflection_resolver;
    public function __construct(Reflection_Provider $reflection_provider, Node_Type_Resolver $node_type_resolver, Node_Name_Resolver $node_name_resolver, Class_Analyzer $class_analyzer, \Rector\Reflection\Method_Reflection_Resolver $method_reflection_resolver)
    {
        $this->reflection_provider = $reflection_provider;
        $this->node_type_resolver = $node_type_resolver;
        $this->node_name_resolver = $node_name_resolver;
        $this->class_analyzer = $class_analyzer;
        $this->method_reflection_resolver = $method_reflection_resolver;
    }
    /**
     * @api
     */
    public function resolve_class_and_anonymous_class(Class_Like $class_like): Class_Reflection
    {
        if ($class_like instanceof Class_ && $this->class_analyzer->is_anonymous_class($class_like)) {
            $class_like_scope = $class_like->get_attribute(Attribute_Key::SCOPE);
            if (!$class_like_scope instanceof Scope) {
                throw new Should_Not_Happen_Exception();
            }
            return $this->reflection_provider->get_anonymous_class_reflection($class_like, $class_like_scope);
        }
        $class_name = (string) $this->node_name_resolver->get_name($class_like);
        return $this->reflection_provider->get_class($class_name);
    }
    public function resolve_class_reflection(Node $node): ?Class_Reflection
    {
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return null;
        }
        return $scope->get_class_reflection();
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\NullsafeMethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $node
     */
    public function resolve_class_reflection_source_object($node): ?Class_Reflection
    {
        $object_type = $node instanceof Static_Call || $node instanceof Static_Property_Fetch ? $this->node_type_resolver->get_type($node->class) : $this->node_type_resolver->get_type($node->var);
        $class_name = Class_Name_From_Object_Type_Resolver::resolve($object_type);
        if ($class_name === null) {
            return null;
        }
        if (!$this->reflection_provider->has_class($class_name)) {
            return null;
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        if ($node instanceof Property_Fetch || $node instanceof Static_Property_Fetch) {
            $property_name = (string) $this->node_name_resolver->get_name($node->name);
            if (!$class_reflection->has_native_property($property_name)) {
                return null;
            }
            $property = $class_reflection->get_native_property($property_name);
            if ($property->is_private()) {
                return $class_reflection;
            }
            if ($this->reflection_provider->has_class($property->get_declaring_class()->get_name())) {
                return $this->reflection_provider->get_class($property->get_declaring_class()->get_name());
            }
            return $class_reflection;
        }
        $method_name = (string) $this->node_name_resolver->get_name($node->name);
        if (!$class_reflection->has_native_method($method_name)) {
            return null;
        }
        $extended_method_reflection = $class_reflection->get_native_method($method_name);
        if ($extended_method_reflection->is_private()) {
            return $class_reflection;
        }
        if ($this->reflection_provider->has_class($extended_method_reflection->get_declaring_class()->get_name())) {
            return $this->reflection_provider->get_class($extended_method_reflection->get_declaring_class()->get_name());
        }
        return $class_reflection;
    }
    /**
     * @param class-string $className
     */
    public function resolve_method_reflection(string $class_name, string $method_name, ?Scope $scope): ?Method_Reflection
    {
        return $this->method_reflection_resolver->resolve_method_reflection($class_name, $method_name, $scope);
    }
    public function resolve_method_reflection_from_static_call(Static_Call $static_call): ?Method_Reflection
    {
        $object_type = $this->node_type_resolver->get_type($static_call->class);
        if ($object_type instanceof Shortened_Object_Type || $object_type instanceof Aliased_Object_Type) {
            /** @var array<class-string> $classNames */
            $class_names = [$object_type->get_fully_qualified_name()];
        } else {
            /** @var array<class-string> $classNames */
            $class_names = $object_type->get_object_class_names();
        }
        $method_name = $this->node_name_resolver->get_name($static_call->name);
        if ($method_name === null) {
            return null;
        }
        $scope = $static_call->get_attribute(Attribute_Key::SCOPE);
        foreach ($class_names as $class_name) {
            $method_reflection = $this->resolve_method_reflection($class_name, $method_name, $scope);
            if ($method_reflection instanceof Method_Reflection) {
                return $method_reflection;
            }
        }
        return null;
    }
    public function resolve_method_reflection_from_method_call(Method_Call $method_call): ?Method_Reflection
    {
        $caller_type = $this->node_type_resolver->get_type($method_call->var);
        if ($caller_type instanceof Benevolent_Union_Type) {
            $caller_type = Type_Combinator::remove_falsey($caller_type);
        }
        $class_name = Class_Name_From_Object_Type_Resolver::resolve($caller_type);
        if ($class_name === null) {
            return null;
        }
        $method_name = $this->node_name_resolver->get_name($method_call->name);
        if ($method_name === null) {
            return null;
        }
        $scope = $method_call->get_attribute(Attribute_Key::SCOPE);
        return $this->resolve_method_reflection($class_name, $method_name, $scope);
    }
    /**
     * @return \PHPStan\Reflection\MethodReflection|\PHPStan\Reflection\FunctionReflection|null
     */
    public function resolve_function_like_reflection_from_call(Call_Like $call_like)
    {
        if ($call_like instanceof Method_Call) {
            return $this->resolve_method_reflection_from_method_call($call_like);
        }
        if ($call_like instanceof Static_Call) {
            return $this->resolve_method_reflection_from_static_call($call_like);
        }
        if ($call_like instanceof New_) {
            return $this->resolve_method_reflection_from_new($call_like);
        }
        if ($call_like instanceof Func_Call) {
            return $this->resolve_function_reflection_from_func_call($call_like);
        }
        // todo: support NullsafeMethodCall
        return null;
    }
    /**
     * @api used in rector-laravel
     */
    public function resolve_method_reflection_from_class_method(Class_Method $class_method, Scope $scope): ?Method_Reflection
    {
        $class_reflection = $scope->get_class_reflection();
        if (!$class_reflection instanceof Class_Reflection) {
            return null;
        }
        $class_name = $class_reflection->get_name();
        $method_name = $this->node_name_resolver->get_name($class_method);
        return $this->resolve_method_reflection($class_name, $method_name, $scope);
    }
    public function resolve_method_reflection_from_new(New_ $new): ?Method_Reflection
    {
        $new_class_type = $this->node_type_resolver->get_type($new->class);
        $class_name = Class_Name_From_Object_Type_Resolver::resolve($new_class_type);
        if ($class_name === null) {
            return null;
        }
        $scope = $new->get_attribute(Attribute_Key::SCOPE);
        return $this->resolve_method_reflection($class_name, Method_Name::CONSTRUCT, $scope);
    }
    public function resolve_constructor_reflection_from_attribute(Attribute $attribute): ?Method_Reflection
    {
        $attribute_class_type = $this->node_type_resolver->get_type($attribute->name);
        $class_name = Class_Name_From_Object_Type_Resolver::resolve($attribute_class_type);
        if ($class_name === null) {
            return null;
        }
        $scope = $attribute->get_attribute(Attribute_Key::SCOPE);
        return $this->resolve_method_reflection($class_name, Method_Name::CONSTRUCT, $scope);
    }
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
     */
    public function resolve_property_reflection_from_property_fetch($property_fetch): ?Php_Property_Reflection
    {
        $property_name = $this->node_name_resolver->get_name($property_fetch->name);
        if ($property_name === null) {
            return null;
        }
        $fetchee_type = $property_fetch instanceof Property_Fetch ? $this->node_type_resolver->get_type($property_fetch->var) : $this->node_type_resolver->get_type($property_fetch->class);
        $class_name = Class_Name_From_Object_Type_Resolver::resolve($fetchee_type);
        if ($class_name === null) {
            return null;
        }
        if (!$this->reflection_provider->has_class($class_name)) {
            return null;
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        if (!$class_reflection->has_native_property($property_name)) {
            return null;
        }
        return $class_reflection->get_native_property($property_name);
    }
    /**
     * @return \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null
     */
    private function resolve_function_reflection_from_func_call(Func_Call $func_call): ?\Php_Stan\Reflection\Function_Reflection
    {
        if (!$func_call->name instanceof Name) {
            return null;
        }
        $function_name = new Name((string) $this->node_name_resolver->get_name($func_call));
        if ($this->reflection_provider->has_function($function_name, null)) {
            return $this->reflection_provider->get_function($function_name, null);
        }
        return null;
    }
}