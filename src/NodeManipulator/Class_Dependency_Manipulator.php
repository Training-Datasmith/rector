<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Property;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Type;
use Rector\Enum\Object_Reference;
use Rector\Node_Analyzer\Property_Presence_Checker;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Parser\Ast_Resolver;
use Rector\Php_Parser\Node\Node_Factory;
use Rector\Post_Rector\Value_Object\Property_Metadata;
use Rector\Reflection\Reflection_Resolver;
use Rector\Type_Declaration\Node_Analyzer\Autowired_Class_Method_Or_Property_Analyzer;
use Rector\Value_Object\Method_Name;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @see \Rector\Tests\NodeManipulator\ClassDependencyManipulatorTest
 */
final class Class_Dependency_Manipulator
{
    /**
     * @readonly
     */
    private \Rector\Node_Manipulator\Class_Insert_Manipulator $class_insert_manipulator;
    /**
     * @readonly
     */
    private \Rector\Node_Manipulator\Class_Method_Assign_Manipulator $class_method_assign_manipulator;
    /**
     * @readonly
     */
    private Node_Factory $node_factory;
    /**
     * @readonly
     */
    private \Rector\Node_Manipulator\Stmts_Manipulator $stmts_manipulator;
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @readonly
     */
    private Property_Presence_Checker $property_presence_checker;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Autowired_Class_Method_Or_Property_Analyzer $autowired_class_method_or_property_analyzer;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    /**
     * @readonly
     */
    private Ast_Resolver $ast_resolver;
    public function __construct(\Rector\Node_Manipulator\Class_Insert_Manipulator $class_insert_manipulator, \Rector\Node_Manipulator\Class_Method_Assign_Manipulator $class_method_assign_manipulator, Node_Factory $node_factory, \Rector\Node_Manipulator\Stmts_Manipulator $stmts_manipulator, Php_Version_Provider $php_version_provider, Property_Presence_Checker $property_presence_checker, Node_Name_Resolver $node_name_resolver, Autowired_Class_Method_Or_Property_Analyzer $autowired_class_method_or_property_analyzer, Reflection_Resolver $reflection_resolver, Ast_Resolver $ast_resolver)
    {
        $this->class_insert_manipulator = $class_insert_manipulator;
        $this->class_method_assign_manipulator = $class_method_assign_manipulator;
        $this->node_factory = $node_factory;
        $this->stmts_manipulator = $stmts_manipulator;
        $this->php_version_provider = $php_version_provider;
        $this->property_presence_checker = $property_presence_checker;
        $this->node_name_resolver = $node_name_resolver;
        $this->autowired_class_method_or_property_analyzer = $autowired_class_method_or_property_analyzer;
        $this->reflection_resolver = $reflection_resolver;
        $this->ast_resolver = $ast_resolver;
    }
    public function add_constructor_dependency(Class_ $class, Property_Metadata $property_metadata): void
    {
        // already has property as dependency? skip it
        if ($this->has_class_property_and_dependency($class, $property_metadata)) {
            return;
        }
        // special case for Symfony @required
        $autowire_class_method = $this->autowired_class_method_or_property_analyzer->match_autowired_method_in_class($class);
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::PROPERTY_PROMOTION) || $autowire_class_method instanceof Class_Method) {
            $this->class_insert_manipulator->add_property_to_class($class, $property_metadata->get_name(), $property_metadata->get_type());
        }
        // in case of existing autowire method, re-use it
        if ($autowire_class_method instanceof Class_Method) {
            $assign = $this->node_factory->create_property_assignment($property_metadata->get_name());
            $this->class_method_assign_manipulator->add_parameter_and_assign_to_method($autowire_class_method, $property_metadata->get_name(), $property_metadata->get_type(), $assign);
            return;
        }
        $construct_class_method = $this->resolve_construct($class);
        // add PHP 8.0 promoted property
        if ($this->should_add_promoted_property($class, $property_metadata)) {
            $this->add_promoted_property($class, $property_metadata, $construct_class_method);
            return;
        }
        $assign = $this->node_factory->create_property_assignment($property_metadata->get_name());
        $this->add_constructor_dependency_with_custom_assign($class, $property_metadata->get_name(), $property_metadata->get_type(), $assign);
    }
    /**
     * @api doctrine
     */
    public function add_constructor_dependency_with_custom_assign(Class_ $class, string $name, ?Type $type, Assign $assign): void
    {
        /** @var ClassMethod|null $constructClassMethod */
        $construct_class_method = $this->resolve_construct($class);
        if ($construct_class_method instanceof Class_Method) {
            if (!$class->get_method(Method_Name::CONSTRUCT) instanceof Class_Method) {
                $parent_args = [];
                foreach ($construct_class_method->params as $original_param) {
                    $parent_args[] = new Arg(new Variable((string) $this->node_name_resolver->get_name($original_param->var)));
                }
                $construct_class_method->stmts = [new Expression(new Static_Call(new Name(Object_Reference::PARENT), Method_Name::CONSTRUCT, $parent_args))];
                $this->class_insert_manipulator->add_as_first_method($class, $construct_class_method);
                $this->class_method_assign_manipulator->add_parameter_and_assign_to_method($construct_class_method, $name, $type, $assign);
            } else {
                $this->class_method_assign_manipulator->add_parameter_and_assign_to_method($construct_class_method, $name, $type, $assign);
            }
            return;
        }
        $construct_class_method = $this->node_factory->create_public_method(Method_Name::CONSTRUCT);
        $this->class_method_assign_manipulator->add_parameter_and_assign_to_method($construct_class_method, $name, $type, $assign);
        $this->class_insert_manipulator->add_as_first_method($class, $construct_class_method);
    }
    /**
     * @api doctrine
     * @param Stmt[] $stmts
     */
    public function add_stmts_to_constructor_if_not_there_yet(Class_ $class, array $stmts): void
    {
        $class_method = $class->get_method(Method_Name::CONSTRUCT);
        if (!$class_method instanceof Class_Method) {
            $class_method = $this->node_factory->create_public_method(Method_Name::CONSTRUCT);
            // keep parent constructor call
            if ($this->has_class_parent_class_method($class, Method_Name::CONSTRUCT)) {
                $class_method->stmts[] = $this->create_parent_class_method_call(Method_Name::CONSTRUCT);
            }
            $class_method->stmts = array_merge((array) $class_method->stmts, $stmts);
            $class->stmts = array_merge($class->stmts, [$class_method]);
            return;
        }
        $stmts = $this->stmts_manipulator->filter_out_existing_stmts($class_method, $stmts);
        // all stmts are already there → skip
        if ($stmts === []) {
            return;
        }
        $class_method->stmts = array_merge($stmts, (array) $class_method->stmts);
    }
    private function resolve_construct(Class_ $class): ?Class_Method
    {
        /** @var ClassMethod|null $constructorMethod */
        $constructor_method = $class->get_method(Method_Name::CONSTRUCT);
        // exists in current class
        if ($constructor_method instanceof Class_Method) {
            return $constructor_method;
        }
        // lookup parent, found first found (nearest parent constructor to follow)
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class);
        if (!$class_reflection instanceof Class_Reflection) {
            return null;
        }
        $ancestors = array_filter($class_reflection->get_ancestors(), static fn(Class_Reflection $ancestor): bool => $ancestor->get_name() !== $class_reflection->get_name());
        foreach ($ancestors as $ancestor) {
            if (!$ancestor->has_native_method(Method_Name::CONSTRUCT)) {
                continue;
            }
            $parent_class = $this->ast_resolver->resolve_class_from_class_reflection($ancestor);
            if (!$parent_class instanceof Class_Like) {
                continue;
            }
            $parent_constructor_method = $parent_class->get_method(Method_Name::CONSTRUCT);
            if (!$parent_constructor_method instanceof Class_Method) {
                continue;
            }
            if ($parent_constructor_method->is_private()) {
                // stop, nearest __construct() uses private visibility
                // which parent::__construct() will cause error
                break;
            }
            $constructor_method = clone $parent_constructor_method;
            // reprint parent method node to avoid invalid tokens
            $this->node_factory->create_reprinted_node($constructor_method);
            return $constructor_method;
        }
        return null;
    }
    private function add_promoted_property(Class_ $class, Property_Metadata $property_metadata, ?Class_Method $construct_class_method): void
    {
        $param = $this->node_factory->create_promoted_property_param($property_metadata);
        if ($construct_class_method instanceof Class_Method) {
            // parameter is already added
            if ($this->has_method_parameter($construct_class_method, $property_metadata->get_name())) {
                return;
            }
            // found construct, but only on parent, add to current class
            if (!$class->get_method(Method_Name::CONSTRUCT) instanceof Class_Method) {
                $parent_args = [];
                foreach ($construct_class_method->params as $original_param) {
                    $parent_args[] = new Arg(new Variable((string) $this->node_name_resolver->get_name($original_param->var)));
                }
                $construct_class_method->params[] = $param;
                $construct_class_method->stmts = [new Expression(new Static_Call(new Name(Object_Reference::PARENT), Method_Name::CONSTRUCT, $parent_args))];
                $this->class_insert_manipulator->add_as_first_method($class, $construct_class_method);
            } else {
                $construct_class_method->params[] = $param;
            }
        } else {
            $construct_class_method = $this->node_factory->create_public_method(Method_Name::CONSTRUCT);
            $construct_class_method->params[] = $param;
            $this->class_insert_manipulator->add_as_first_method($class, $construct_class_method);
        }
    }
    private function has_class_parent_class_method(Class_ $class, string $method_name): bool
    {
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class);
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        foreach ($class_reflection->get_parents() as $parent_class_reflection) {
            if ($parent_class_reflection->has_method($method_name)) {
                return \true;
            }
        }
        return \false;
    }
    private function create_parent_class_method_call(string $method_name): Expression
    {
        $static_call = new Static_Call(new Name(Object_Reference::PARENT), $method_name);
        return new Expression($static_call);
    }
    private function is_param_in_constructor(Class_ $class, string $property_name): bool
    {
        $construct_class_method = $class->get_method(Method_Name::CONSTRUCT);
        if (!$construct_class_method instanceof Class_Method) {
            return \false;
        }
        foreach ($construct_class_method->params as $param) {
            if ($this->node_name_resolver->is_name($param, $property_name)) {
                return \true;
            }
        }
        return \false;
    }
    private function has_class_property_and_dependency(Class_ $class, Property_Metadata $property_metadata): bool
    {
        $property = $this->property_presence_checker->get_class_context_property($class, $property_metadata);
        if ($property === null) {
            return \false;
        }
        if (!$this->autowired_class_method_or_property_analyzer->detect($property)) {
            return $this->is_param_in_constructor($class, $property_metadata->get_name());
        }
        // is inject/autowired property?
        return $property instanceof Property;
    }
    private function has_method_parameter(Class_Method $class_method, string $name): bool
    {
        foreach ($class_method->params as $param) {
            if ($this->node_name_resolver->is_name($param->var, $name)) {
                return \true;
            }
        }
        return \false;
    }
    private function should_add_promoted_property(Class_ $class, Property_Metadata $property_metadata): bool
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::PROPERTY_PROMOTION)) {
            return \false;
        }
        // only if the property does not exist yet
        $existing_property = $class->get_property($property_metadata->get_name());
        return !$existing_property instanceof Property;
    }
}