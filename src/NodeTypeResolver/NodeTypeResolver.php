<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_Dim_Fetch;
use Php_Parser\Node\Expr\Binary_Op\Coalesce;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Ternary;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Nullable_Type;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Union_Type as NodeUnionType;
use Php_Stan\Analyser\Scope;
use Php_Stan\Broker\Class_Not_Found_Exception;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Native\Native_Function_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Constant\Constant_String_Type;
use Php_Stan\Type\Error_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Never_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\This_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Type_With_Class_Name;
use Php_Stan\Type\Union_Type;
use Rector\Configuration\Renamed_Classes_Data_Collector;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Analyzer\Class_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Aware_Interface;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Php_Stan\Object_Without_Class_Type_With_Parent_Types;
use Rector\Php\Php_Version_Provider;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type;
use Rector\Type_Declaration\Php_Stan\Object_Type_Specifier;
use Rector\Value_Object\Php_Version;
final class Node_Type_Resolver
{
    /**
     * @readonly
     */
    private Object_Type_Specifier $object_type_specifier;
    /**
     * @readonly
     */
    private Class_Analyzer $class_analyzer;
    /**
     * @readonly
     */
    private \Rector\Node_Type_Resolver\Node_Type_Corrector $node_type_corrector;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Renamed_Classes_Data_Collector $renamed_classes_data_collector;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @var string
     */
    private const ERROR_MESSAGE = '%s itself does not have any type. Check the %s node instead';
    /**
     * @var array<class-string<Node>, NodeTypeResolverInterface>
     */
    private array $node_type_resolvers = [];
    /**
     * @param NodeTypeResolverInterface[] $nodeTypeResolvers
     */
    public function __construct(Object_Type_Specifier $object_type_specifier, Class_Analyzer $class_analyzer, \Rector\Node_Type_Resolver\Node_Type_Corrector $node_type_corrector, Reflection_Provider $reflection_provider, Renamed_Classes_Data_Collector $renamed_classes_data_collector, Node_Name_Resolver $node_name_resolver, Php_Version_Provider $php_version_provider, iterable $node_type_resolvers)
    {
        $this->object_type_specifier = $object_type_specifier;
        $this->class_analyzer = $class_analyzer;
        $this->node_type_corrector = $node_type_corrector;
        $this->reflection_provider = $reflection_provider;
        $this->renamed_classes_data_collector = $renamed_classes_data_collector;
        $this->node_name_resolver = $node_name_resolver;
        $this->php_version_provider = $php_version_provider;
        foreach ($node_type_resolvers as $node_type_resolver) {
            if ($node_type_resolver instanceof Node_Type_Resolver_Aware_Interface) {
                $node_type_resolver->autowire($this);
            }
            foreach ($node_type_resolver->get_node_classes() as $node_class) {
                $this->node_type_resolvers[$node_class] = $node_type_resolver;
            }
        }
    }
    /**
     * @api doctrine symfony
     * @param ObjectType[] $requiredTypes
     */
    public function is_object_types(Node $node, array $required_types): bool
    {
        foreach ($required_types as $required_type) {
            if ($this->is_object_type($node, $required_type)) {
                return \true;
            }
        }
        return \false;
    }
    public function is_object_type(Node $node, Object_Type $required_object_type): bool
    {
        if ($node instanceof Class_Const_Fetch) {
            return \false;
        }
        // warn about invalid use of this method
        if ($node instanceof Class_Method || $node instanceof Class_Const) {
            throw new Should_Not_Happen_Exception(sprintf(self::ERROR_MESSAGE, get_class($node), Class_Like::class));
        }
        $resolved_type = $this->get_type($node);
        // cover call $this on trait
        if ($resolved_type instanceof Error_Type && ($node instanceof Variable && $this->node_name_resolver->is_name($node, 'this'))) {
            $scope = $node->get_attribute(Attribute_Key::SCOPE);
            if (!$scope instanceof Scope) {
                return \false;
            }
            $class_reflection = $scope->get_class_reflection();
            if (!$class_reflection instanceof Class_Reflection) {
                return \false;
            }
            if ($class_reflection->is_trait()) {
                $resolved_type = new Object_Type($class_reflection->get_name());
            }
        }
        if ($resolved_type instanceof Mixed_Type) {
            return \false;
        }
        if ($resolved_type instanceof This_Type) {
            $resolved_type = $resolved_type->get_static_object_type();
        }
        if ($resolved_type instanceof Object_Type) {
            try {
                return $this->resolve_object_type($resolved_type, $required_object_type);
            } catch (Class_Not_Found_Exception $exception) {
                // in some type checks, the provided type in rector.php configuration does not have to exists
                return \false;
            }
        }
        if ($resolved_type instanceof Object_Without_Class_Type) {
            return $this->is_match_object_without_class_type($resolved_type, $required_object_type);
        }
        return $this->is_matching_union_type($resolved_type, $required_object_type);
    }
    public function get_type(Node $node): Type
    {
        if ($node instanceof Nullable_Type) {
            $type = $this->get_type($node->type);
            if (!$type instanceof Mixed_Type) {
                return new Union_Type([$type, new Null_Type()]);
            }
        }
        if ($node instanceof Ternary) {
            $ternary_type = $this->resolve_ternary_type($node);
            if (!$ternary_type instanceof Mixed_Type) {
                return $ternary_type;
            }
        }
        if ($node instanceof Coalesce) {
            $first = $this->get_type($node->left);
            $second = $this->get_type($node->right);
            if ($this->is_union_typeable($first, $second)) {
                return new Union_Type([$first, $second]);
            }
        }
        $type = $this->resolve_by_node_type_resolvers($node);
        if ($type instanceof Type) {
            $type = $this->node_type_corrector->correct_type($type);
            if ($type instanceof Object_Type) {
                $scope = $node->get_attribute(Attribute_Key::SCOPE);
                $type = $this->object_type_specifier->narrow_to_fully_qualified_or_aliased_object_type($node, $type, $scope, \true);
            }
            return $type;
        }
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return new Mixed_Type();
        }
        if ($node instanceof Node_Union_Type) {
            $types = [];
            foreach ($node->types as $type) {
                $types[] = $this->get_type($type);
            }
            return new Union_Type($types);
        }
        if (!$node instanceof Expr) {
            return new Mixed_Type();
        }
        $type = $this->node_type_corrector->correct_type($scope->get_type($node));
        // hot fix for phpstan not resolving chain method calls
        if (!$node instanceof Method_Call) {
            return $type;
        }
        if (!$type instanceof Mixed_Type) {
            return $type;
        }
        return $this->get_type($node->var);
    }
    /**
     * e.g. string|null, ObjectNull|null
     */
    public function is_nullable_type(Node $node): bool
    {
        $node_type = $this->get_type($node);
        return Type_Combinator::contains_null($node_type);
    }
    public function get_native_type(Expr $expr): Type
    {
        $scope = $expr->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return new Mixed_Type();
        }
        // cover direct New_ class
        if ($this->class_analyzer->is_anonymous_class($expr)) {
            $type = $this->node_type_resolvers[New_::class]->resolve($expr);
            if ($type instanceof Object_Without_Class_Type) {
                return $type;
            }
        }
        $type = $this->resolve_native_type_with_builtin_method_call_fallback($expr, $scope);
        if ($expr instanceof Array_Dim_Fetch) {
            $type = $this->resolve_array_dim_fetch_type($expr, $scope, $type);
        }
        if (!$type instanceof Union_Type) {
            if ($this->is_anonymous_object_type($type)) {
                return new Object_Without_Class_Type();
            }
            return $this->node_type_corrector->correct_type($type);
        }
        return $this->resolve_native_union_type($type);
    }
    public function is_number_type(Expr $expr): bool
    {
        $node_type = $this->get_native_type($expr);
        if ($node_type->is_integer()->yes()) {
            return \true;
        }
        return $node_type->is_float()->yes();
    }
    /**
     * @template TType as Type
     *
     * @param class-string<TType> $desiredType
     * @return TType|null
     */
    public function match_nullable_type_of_specific_type(Expr $expr, string $desired_type): ?Type
    {
        $node_type = $this->get_type($expr);
        if (!$node_type instanceof Union_Type) {
            return null;
        }
        $bare_type = Type_Combinator::remove_null($node_type);
        if (!$bare_type instanceof $desired_type) {
            return null;
        }
        return $bare_type;
    }
    public function get_fully_qualified_class_name(Type_With_Class_Name $type_with_class_name): string
    {
        if ($type_with_class_name instanceof Shortened_Object_Type) {
            return $type_with_class_name->get_fully_qualified_name();
        }
        if ($type_with_class_name instanceof Aliased_Object_Type) {
            return $type_with_class_name->get_fully_qualified_name();
        }
        return $type_with_class_name->get_class_name();
    }
    public function is_method_static_call_or_class_method_object_type(Node $node, Object_Type $object_type): bool
    {
        if ($node instanceof Method_Call || $node instanceof Nullsafe_Method_Call) {
            if ($this->is_enum_type_match($node, $object_type)) {
                return \true;
            }
            // method call is variable return
            return $this->is_object_type($node->var, $object_type);
        }
        if ($node instanceof Static_Call) {
            return $this->is_object_type($node->class, $object_type);
        }
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return \false;
        }
        $class_reflection = $scope->get_class_reflection();
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        if ($class_reflection->get_name() === $object_type->get_class_name()) {
            return \true;
        }
        if ($class_reflection->is($object_type->get_class_name())) {
            return \true;
        }
        return $class_reflection->has_trait_use($object_type->get_class_name());
    }
    /**
     * Allow pull type from
     *
     *      - native function
     *      - always defined by assignment
     *
     * eg:
     *
     *  $parts = parse_url($url);
     *  if (!empty($parts['host'])) { }
     *
     * or
     *
     *  $parts = ['host' => 'foo'];
     *  if (!empty($parts['host'])) { }
     */
    private function resolve_array_dim_fetch_type(Array_Dim_Fetch $array_dim_fetch, Scope $scope, Type $original_native_type): Type
    {
        $native_variable_type = $scope->get_native_type($array_dim_fetch->var);
        if ($native_variable_type instanceof Mixed_Type || $native_variable_type instanceof Array_Type && $native_variable_type->get_iterable_value_type() instanceof Mixed_Type) {
            return $original_native_type;
        }
        $type = $scope->get_type($array_dim_fetch);
        if (!$array_dim_fetch->dim instanceof String_) {
            return $type;
        }
        $variable_type = $scope->get_type($array_dim_fetch->var);
        if (!$variable_type instanceof Constant_Array_Type) {
            return $type;
        }
        $optional_keys = $variable_type->get_optional_keys();
        foreach ($variable_type->get_key_types() as $key => $key_type) {
            if (!$key_type instanceof Constant_String_Type) {
                continue;
            }
            if ($key_type->get_value() !== $array_dim_fetch->dim->value) {
                continue;
            }
            if (!in_array($key, $optional_keys, \true)) {
                continue;
            }
            return $original_native_type;
        }
        return $type;
    }
    private function resolve_native_union_type(Union_Type $union_type): Union_Type
    {
        $has_changed = \false;
        $types = $union_type->get_types();
        foreach ($types as $key => $child_type) {
            if ($this->is_anonymous_object_type($child_type)) {
                $types[$key] = new Object_Without_Class_Type();
                $has_changed = \true;
            }
        }
        if ($has_changed) {
            return new Union_Type($types);
        }
        return $union_type;
    }
    private function is_match_object_without_class_type(Object_Without_Class_Type $object_without_class_type, Object_Type $required_object_type): bool
    {
        if ($object_without_class_type instanceof Object_Without_Class_Type_With_Parent_Types) {
            foreach ($object_without_class_type->get_parent_types() as $type_with_class_name) {
                if ($required_object_type->is_super_type_of($type_with_class_name)->yes()) {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function is_anonymous_object_type(Type $type): bool
    {
        if (!$type instanceof Object_Type) {
            return \false;
        }
        $class_reflection = $type->get_class_reflection();
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        return $class_reflection->is_anonymous();
    }
    private function is_union_typeable(Type $first, Type $second): bool
    {
        return !$first instanceof Union_Type && !$second instanceof Union_Type && !$second->is_null()->yes();
    }
    private function is_matching_union_type(Type $resolved_type, Object_Type $required_object_type): bool
    {
        $type = Type_Combinator::remove_null($resolved_type);
        if ($type instanceof Never_Type) {
            return \false;
        }
        // for falsy nullables
        $type = Type_Combinator::remove($type, new Constant_Boolean_Type(\false));
        if ($type instanceof Object_Without_Class_Type) {
            return $this->is_match_object_without_class_type($type, $required_object_type);
        }
        return $required_object_type->is_super_type_of($type)->yes();
    }
    private function resolve_by_node_type_resolvers(Node $node): ?Type
    {
        foreach ($this->node_type_resolvers as $node_class => $node_type_resolver) {
            if (!$node instanceof $node_class) {
                continue;
            }
            return $node_type_resolver->resolve($node);
        }
        return null;
    }
    private function is_object_type_of_object_type(Object_Type $resolved_object_type, Object_Type $required_object_type): bool
    {
        $required_class_name = $required_object_type->get_class_name();
        $resolved_class_name = $resolved_object_type->get_class_name();
        if ($resolved_class_name === $required_class_name) {
            return \true;
        }
        if ($resolved_object_type->is_instance_of($required_class_name)->yes()) {
            return \true;
        }
        if (!$this->reflection_provider->has_class($required_class_name)) {
            return \false;
        }
        $required_class_reflection = $this->reflection_provider->get_class($required_class_name);
        if ($required_class_reflection->is_trait()) {
            if (!$this->reflection_provider->has_class($resolved_class_name)) {
                return \false;
            }
            $resolved_class_reflection = $this->reflection_provider->get_class($resolved_class_name);
            foreach ($resolved_class_reflection->get_ancestors() as $ancestor_class_reflection) {
                if ($ancestor_class_reflection->has_trait_use($required_class_name)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function resolve_object_type(Object_Type $resolved_object_type, Object_Type $required_object_type): bool
    {
        $renamed_object_type = $this->renamed_classes_data_collector->match_class_name($resolved_object_type);
        if (!$renamed_object_type instanceof Object_Type) {
            return $this->is_object_type_of_object_type($resolved_object_type, $required_object_type);
        }
        if (!$this->is_object_type_of_object_type($renamed_object_type, $required_object_type)) {
            return $this->is_object_type_of_object_type($resolved_object_type, $required_object_type);
        }
        return \true;
    }
    /**
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\UnionType
     */
    private function resolve_ternary_type(Ternary $ternary)
    {
        if ($ternary->if instanceof Expr) {
            $first = $this->get_type($ternary->if);
            $second = $this->get_type($ternary->else);
            if ($this->is_union_typeable($first, $second)) {
                return new Union_Type([$first, $second]);
            }
        }
        $cond_type = $this->get_type($ternary->cond);
        if ($this->is_nullable_type($ternary->cond) && $cond_type instanceof Union_Type) {
            $first = $cond_type->get_types()[0];
            $second = $this->get_type($ternary->else);
            if ($this->is_union_typeable($first, $second)) {
                return new Union_Type([$first, $second]);
            }
        }
        return new Mixed_Type();
    }
    /**
     * Method calls on native PHP classes report mixed,
     * even on strict known type; this fallbacks to getType() that provides correct type
     */
    private function resolve_native_type_with_builtin_method_call_fallback(Expr $expr, Scope $scope): Type
    {
        if ($expr instanceof Method_Call) {
            $caller_type = $scope->get_type($expr->var);
            if ($caller_type instanceof Object_Type && $caller_type->get_class_reflection() instanceof Class_Reflection && $caller_type->get_class_reflection()->is_builtin()) {
                return $scope->get_type($expr);
            }
        }
        if ($expr instanceof Func_Call) {
            if (!$expr->name instanceof Name) {
                return $scope->get_native_type($expr);
            }
            $function_name = new Name((string) $this->node_name_resolver->get_name($expr));
            if (!$this->reflection_provider->has_function($function_name, null)) {
                return $scope->get_native_type($expr);
            }
            $function_reflection = $this->reflection_provider->get_function($function_name, null);
            if (!$function_reflection instanceof Native_Function_Reflection) {
                return $scope->get_native_type($expr);
            }
            if ($this->is_substr_on_php74($expr)) {
                return new Union_Type([new String_Type(), new Constant_Boolean_Type(\false)]);
            }
            return $scope->get_type($expr);
        }
        return $scope->get_native_type($expr);
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\NullsafeMethodCall $call
     */
    private function is_enum_type_match($call, Object_Type $object_type): bool
    {
        if (!$call->var instanceof Class_Const_Fetch) {
            return \false;
        }
        // possibly enum
        $class_const_fetch = $call->var;
        if (!$class_const_fetch->class instanceof Fully_Qualified) {
            return \false;
        }
        $class_name = $class_const_fetch->class->to_string();
        if (!$this->reflection_provider->has_class($class_name)) {
            return \false;
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        if (!$class_reflection->is_enum()) {
            return \false;
        }
        return $class_reflection->get_name() === $object_type->get_class_name();
    }
    /**
     * substr can return false on php 7.x and bellow
     */
    private function is_substr_on_php74(Func_Call $func_call): bool
    {
        if ($func_call->is_first_class_callable()) {
            return \false;
        }
        if (!$this->node_name_resolver->is_name($func_call, 'substr')) {
            return \false;
        }
        return !$this->php_version_provider->is_at_least_php_version(Php_Version::PHP_80);
    }
}