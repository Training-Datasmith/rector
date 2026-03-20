<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Finder;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_Dim_Fetch;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Nullsafe_Property_Fetch;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Property;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Static_Type;
use Rector\Node_Analyzer\Property_Fetch_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Node_Type_Resolver\Php_Stan\Parameters_Acceptor_Selector_Variants_Wrapper;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Ast_Resolver;
use Rector\Php_Parser\Node\Better_Node_Finder;
use Rector\Reflection\Reflection_Resolver;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
final class Property_Fetch_Finder
{
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    /**
     * @readonly
     */
    private Ast_Resolver $ast_resolver;
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @readonly
     */
    private Property_Fetch_Analyzer $property_fetch_analyzer;
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    public function __construct(Better_Node_Finder $better_node_finder, Node_Name_Resolver $node_name_resolver, Reflection_Resolver $reflection_resolver, Ast_Resolver $ast_resolver, Node_Type_Resolver $node_type_resolver, Property_Fetch_Analyzer $property_fetch_analyzer, Simple_Callable_Node_Traverser $simple_callable_node_traverser)
    {
        $this->better_node_finder = $better_node_finder;
        $this->node_name_resolver = $node_name_resolver;
        $this->reflection_resolver = $reflection_resolver;
        $this->ast_resolver = $ast_resolver;
        $this->node_type_resolver = $node_type_resolver;
        $this->property_fetch_analyzer = $property_fetch_analyzer;
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
    }
    /**
     * @return array<PropertyFetch|StaticPropertyFetch>
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param $propertyOrPromotedParam
     */
    public function find_private_property_fetches(Class_ $class, $property_or_promoted_param, Scope $scope): array
    {
        $property_name = $this->resolve_property_name($property_or_promoted_param);
        if ($property_name === null) {
            return [];
        }
        $class_reflection = $this->reflection_resolver->resolve_class_and_anonymous_class($class);
        $nodes = [$class];
        $nodes_trait = $this->ast_resolver->parse_class_reflection_traits($class_reflection);
        $has_trait = $nodes_trait !== [];
        $nodes = array_merge($nodes, $nodes_trait);
        return $this->find_property_fetches_in_class_like($class, $nodes, $property_name, $has_trait, $scope);
    }
    /**
     * @api used by other Rector packages
     * @return PropertyFetch[]|StaticPropertyFetch[]|NullsafePropertyFetch[]
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\ClassMethod $node
     */
    public function find_local_property_fetches_by_name($node, string $param_name): array
    {
        /** @var PropertyFetch[]|StaticPropertyFetch[]|NullsafePropertyFetch[] $foundPropertyFetches */
        $found_property_fetches = $this->better_node_finder->find($this->resolve_nodes_to_locate($node), function (Node $sub_node) use ($param_name): bool {
            if ($sub_node instanceof Property_Fetch) {
                return $this->property_fetch_analyzer->is_local_property_fetch_name($sub_node, $param_name);
            }
            if ($sub_node instanceof Nullsafe_Property_Fetch) {
                return $this->property_fetch_analyzer->is_local_property_fetch_name($sub_node, $param_name);
            }
            if ($sub_node instanceof Static_Property_Fetch) {
                return $this->property_fetch_analyzer->is_local_property_fetch_name($sub_node, $param_name);
            }
            return \false;
        });
        return $found_property_fetches;
    }
    /**
     * @return ArrayDimFetch[]
     */
    public function find_local_property_array_dim_fetches_assigns_by_name(Class_ $class, Property $property): array
    {
        $property_name = $this->node_name_resolver->get_name($property);
        /** @var ArrayDimFetch[] $propertyArrayDimFetches */
        $property_array_dim_fetches = [];
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($this->resolve_nodes_to_locate($class), function (Node $sub_node) use (&$property_array_dim_fetches, $property_name) {
            if (!$sub_node instanceof Assign) {
                return null;
            }
            if (!$sub_node->var instanceof Array_Dim_Fetch) {
                return null;
            }
            $dim_fetch_var = $sub_node->var;
            if (!$dim_fetch_var->var instanceof Property_Fetch && !$dim_fetch_var->var instanceof Static_Property_Fetch) {
                return null;
            }
            if (!$this->property_fetch_analyzer->is_local_property_fetch_name($dim_fetch_var->var, $property_name)) {
                return null;
            }
            $property_array_dim_fetches[] = $dim_fetch_var;
            return null;
        });
        return $property_array_dim_fetches;
    }
    /**
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Trait_ $class
     */
    public function is_local_property_fetch_by_name(Expr $expr, $class, string $property_name): bool
    {
        if (!$expr instanceof Property_Fetch) {
            return \false;
        }
        if (!$this->node_name_resolver->is_name($expr->name, $property_name)) {
            return \false;
        }
        if ($this->node_name_resolver->is_name($expr->var, 'this')) {
            return \true;
        }
        $type = $this->node_type_resolver->get_type($expr->var);
        if ($type instanceof Object_Type || $type instanceof Static_Type) {
            return $this->node_name_resolver->is_name($class, $type->get_class_name());
        }
        return \false;
    }
    /**
     * @return Stmt[]
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\ClassMethod $node
     */
    private function resolve_nodes_to_locate($node): array
    {
        if ($node instanceof Class_Method) {
            return [$node];
        }
        $property_with_hooks = array_filter($node->get_properties(), fn(Property $property): bool => $property->hooks !== []);
        return array_merge($property_with_hooks, $node->get_methods());
    }
    /**
     * @param Stmt[] $stmts
     * @return PropertyFetch[]|StaticPropertyFetch[]
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Trait_ $class
     */
    private function find_property_fetches_in_class_like(\Php_Parser\Node\Stmt\Class_ $class, array $stmts, string $property_name, bool $has_trait, Scope $scope): array
    {
        /** @var PropertyFetch[]|StaticPropertyFetch[] $propertyFetches */
        $property_fetches = $this->better_node_finder->find($stmts, function (Node $sub_node) use ($class, $has_trait, $property_name, $scope): bool {
            if ($sub_node instanceof Method_Call || $sub_node instanceof Static_Call || $sub_node instanceof Func_Call) {
                $this->decorate_property_fetch($sub_node, $scope);
                return \false;
            }
            if ($sub_node instanceof Property_Fetch) {
                if ($this->is_in_anonymous($sub_node, $class, $has_trait)) {
                    return \false;
                }
                return $this->is_name_property_name_equals($sub_node, $property_name, $class);
            }
            if ($sub_node instanceof Static_Property_Fetch) {
                return $this->node_name_resolver->is_name($sub_node->name, $property_name);
            }
            return \false;
        });
        return $property_fetches;
    }
    private function decorate_property_fetch(Node $node, Scope $scope): void
    {
        if (!$node instanceof Method_Call && !$node instanceof Static_Call && !$node instanceof Func_Call) {
            return;
        }
        if ($node->is_first_class_callable()) {
            return;
        }
        foreach ($node->get_args() as $key => $arg) {
            if (!$arg->value instanceof Property_Fetch && !$arg->value instanceof Static_Property_Fetch) {
                continue;
            }
            if (!$this->is_found_by_ref_param($node, $key, $scope)) {
                continue;
            }
            $arg->value->set_attribute(Attribute_Key::IS_USED_AS_ARG_BY_REF_VALUE, \true);
        }
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\FuncCall $node
     */
    private function is_found_by_ref_param($node, int $key, Scope $scope): bool
    {
        $function_like_reflection = $this->reflection_resolver->resolve_function_like_reflection_from_call($node);
        if ($function_like_reflection === null) {
            return \false;
        }
        $parameters_acceptor = Parameters_Acceptor_Selector_Variants_Wrapper::select($function_like_reflection, $node, $scope);
        $parameters = $parameters_acceptor->get_parameters();
        if (!isset($parameters[$key])) {
            return \false;
        }
        return $parameters[$key]->passed_by_reference()->yes();
    }
    /**
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Trait_ $class
     */
    private function is_in_anonymous(Property_Fetch $property_fetch, $class, bool $has_trait): bool
    {
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($property_fetch);
        if (!$class_reflection instanceof Class_Reflection || !$class_reflection->is_class()) {
            return \false;
        }
        if ($class_reflection->get_name() === $this->node_name_resolver->get_name($class)) {
            return \false;
        }
        return !$has_trait;
    }
    /**
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Trait_ $class
     */
    private function is_name_property_name_equals(Property_Fetch $property_fetch, string $property_name, $class): bool
    {
        // early check if property fetch name is not equals with property name
        // so next check is check var name and var type only
        if (!$this->is_local_property_fetch_by_name($property_fetch, $class, $property_name)) {
            return \false;
        }
        $property_fetch_var_type = $this->node_type_resolver->get_type($property_fetch->var);
        $property_fetch_var_type_class_name = Class_Name_From_Object_Type_Resolver::resolve($property_fetch_var_type);
        if ($property_fetch_var_type_class_name === null) {
            return \false;
        }
        $class_like_name = $this->node_name_resolver->get_name($class);
        return $property_fetch_var_type_class_name === $class_like_name;
    }
    /**
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param $propertyOrPromotedParam
     */
    private function resolve_property_name($property_or_promoted_param): ?string
    {
        if ($property_or_promoted_param instanceof Property) {
            return $this->node_name_resolver->get_name($property_or_promoted_param->props[0]);
        }
        return $this->node_name_resolver->get_name($property_or_promoted_param->var);
    }
}