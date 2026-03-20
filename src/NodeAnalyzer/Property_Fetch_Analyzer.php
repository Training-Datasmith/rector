<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Nullsafe_Property_Fetch;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Trait_;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Static_Type;
use Php_Stan\Type\This_Type;
use Rector\Enum\Object_Reference;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Nesting_Scope\Context_Analyzer;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Php_Parser\Ast_Resolver;
use Rector\Php_Parser\Node\Better_Node_Finder;
use Rector\Reflection\Reflection_Resolver;
use Rector\Value_Object\Method_Name;
final class Property_Fetch_Analyzer
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
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
    private Reflection_Resolver $reflection_resolver;
    /**
     * @readonly
     */
    private Context_Analyzer $context_analyzer;
    /**
     * @var string
     */
    private const THIS = 'this';
    public function __construct(Node_Name_Resolver $node_name_resolver, Better_Node_Finder $better_node_finder, Ast_Resolver $ast_resolver, Node_Type_Resolver $node_type_resolver, Reflection_Resolver $reflection_resolver, Context_Analyzer $context_analyzer)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->better_node_finder = $better_node_finder;
        $this->ast_resolver = $ast_resolver;
        $this->node_type_resolver = $node_type_resolver;
        $this->reflection_resolver = $reflection_resolver;
        $this->context_analyzer = $context_analyzer;
    }
    public function is_local_property_fetch(Node $node): bool
    {
        if (!$node instanceof Property_Fetch && !$node instanceof Static_Property_Fetch && !$node instanceof Nullsafe_Property_Fetch) {
            return \false;
        }
        $variable_type = $node instanceof Static_Property_Fetch ? $this->node_type_resolver->get_type($node->class) : $this->node_type_resolver->get_type($node->var);
        // patch clone usage
        // @see https://github.com/phpstan/phpstan-src/commit/020adb548011c098cdb2e061019346b0a838c6a4
        // @see https://github.com/rectorphp/rector-src/pull/7622
        if ($variable_type instanceof Static_Type && !$variable_type instanceof This_Type) {
            $variable_type = $variable_type->get_static_object_type();
        }
        if ($variable_type instanceof Object_Type) {
            $class_reflection = $this->reflection_resolver->resolve_class_reflection($node);
            if ($class_reflection instanceof Class_Reflection) {
                return $class_reflection->get_name() === $variable_type->get_class_name();
            }
            return \false;
        }
        if (!$variable_type instanceof This_Type) {
            return $this->is_trait_local_property_fetch($node);
        }
        return \true;
    }
    public function is_local_property_fetch_name(Node $node, string $desired_property_name): bool
    {
        if (!$node instanceof Property_Fetch && !$node instanceof Static_Property_Fetch && !$node instanceof Nullsafe_Property_Fetch) {
            return \false;
        }
        if (!$this->node_name_resolver->is_name($node->name, $desired_property_name)) {
            return \false;
        }
        return $this->is_local_property_fetch($node);
    }
    public function contains_local_property_fetch_name(Trait_ $trait, string $property_name): bool
    {
        if ($trait->get_property($property_name) instanceof Property) {
            return \true;
        }
        return (bool) $this->better_node_finder->find_first($trait, fn(Node $node): bool => $this->is_local_property_fetch_name($node, $property_name));
    }
    public function contains_written_property_fetch_name(Trait_ $trait, string $property_name): bool
    {
        if ($trait->get_property($property_name) instanceof Property) {
            return \true;
        }
        return (bool) $this->better_node_finder->find_first($trait, function (Node $node) use ($property_name): bool {
            if (!$this->is_local_property_fetch_name($node, $property_name)) {
                return \false;
            }
            /**
             * @var PropertyFetch|StaticPropertyFetch|NullsafePropertyFetch $node
             */
            if ($this->context_analyzer->is_changeable_context($node)) {
                return \true;
            }
            return $this->context_analyzer->is_left_part_of_assign($node);
        });
    }
    /**
     * @phpstan-assert-if-true PropertyFetch|StaticPropertyFetch $node
     */
    public function is_property_fetch(Node $node): bool
    {
        if ($node instanceof Property_Fetch) {
            return \true;
        }
        return $node instanceof Static_Property_Fetch;
    }
    /**
     * Matches:
     * "$this->someValue = $<variableName>;"
     */
    public function is_variable_assign_to_this_property_fetch(Assign $assign, string $variable_name): bool
    {
        if (!$assign->expr instanceof Variable) {
            return \false;
        }
        if (!$this->node_name_resolver->is_name($assign->expr, $variable_name)) {
            return \false;
        }
        return $this->is_local_property_fetch($assign->var);
    }
    public function is_filled_via_method_call_in_construct_stmts(Class_Like $class_like, string $property_name): bool
    {
        $class_method = $class_like->get_method(Method_Name::CONSTRUCT);
        if (!$class_method instanceof Class_Method) {
            return \false;
        }
        $class_name = (string) $this->node_name_resolver->get_name($class_like);
        $stmts = (array) $class_method->stmts;
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Method_Call && !$stmt->expr instanceof Static_Call) {
                continue;
            }
            $caller_class_method = $this->ast_resolver->resolve_class_method_from_call($stmt->expr);
            if (!$caller_class_method instanceof Class_Method) {
                continue;
            }
            $caller_class_reflection = $this->reflection_resolver->resolve_class_reflection($caller_class_method);
            if (!$caller_class_reflection instanceof Class_Reflection) {
                continue;
            }
            if (!$caller_class_reflection->is_class()) {
                continue;
            }
            $caller_class_name = $caller_class_reflection->get_name();
            $is_found = $this->is_property_assign_found_in_class_method($class_like, $class_name, $caller_class_name, $caller_class_method, $property_name);
            if ($is_found) {
                return \true;
            }
        }
        return \false;
    }
    private function is_trait_local_property_fetch(Node $node): bool
    {
        if ($node instanceof Property_Fetch) {
            if (!$node->var instanceof Variable) {
                return \false;
            }
            return $this->node_name_resolver->is_name($node->var, self::THIS);
        }
        if ($node instanceof Static_Property_Fetch) {
            if (!$node->class instanceof Name) {
                return \false;
            }
            return $this->node_name_resolver->is_names($node->class, [Object_Reference::SELF, Object_Reference::STATIC]);
        }
        return \false;
    }
    private function is_property_assign_found_in_class_method(Class_Like $class_like, string $class_name, string $caller_class_name, Class_Method $class_method, string $property_name): bool
    {
        if ($class_name !== $caller_class_name && !$class_like instanceof Trait_) {
            $object_type = new Object_Type($class_name);
            $caller_object_type = new Object_Type($caller_class_name);
            if (!$caller_object_type->is_super_type_of($object_type)->yes()) {
                return \false;
            }
        }
        foreach ((array) $class_method->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            if ($this->is_local_property_fetch_name($stmt->expr->var, $property_name)) {
                return \true;
            }
        }
        return \false;
    }
}