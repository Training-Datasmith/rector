<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node;

use Php_Parser\Builder\Method;
use Php_Parser\Builder\Param as ParamBuilder;
use Php_Parser\Builder\Property as PropertyBuilder;
use Php_Parser\Builder_Factory;
use Php_Parser\Builder_Helpers;
use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Declare_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Binary_Op\Boolean_And;
use Php_Parser\Node\Expr\Binary_Op\Boolean_Or;
use Php_Parser\Node\Expr\Binary_Op\Concat;
use Php_Parser\Node\Expr\Binary_Op\Identical;
use Php_Parser\Node\Expr\Binary_Op\Not_Identical;
use Php_Parser\Node\Expr\Cast;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Expr\Clone_;
use Php_Parser\Node\Expr\Const_Fetch;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Instanceof_;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Expr\Nullsafe_Property_Fetch;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Expr\Unary_Minus;
use Php_Parser\Node\Expr\Unary_Plus;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Param;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Declare_;
use Php_Parser\Node\Stmt\Property;
use Php_Stan\Type\Type;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
use Rector\Enum\Object_Reference;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Decorator\Property_Type_Decorator;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Post_Rector\Value_Object\Property_Metadata;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @see \Rector\Tests\PhpParser\Node\NodeFactoryTest
 */
final class Node_Factory
{
    /**
     * @readonly
     */
    private Builder_Factory $builder_factory;
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private Property_Type_Decorator $property_type_decorator;
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @var string
     */
    private const THIS = 'this';
    public function __construct(Builder_Factory $builder_factory, Php_Doc_Info_Factory $php_doc_info_factory, Static_Type_Mapper $static_type_mapper, Property_Type_Decorator $property_type_decorator, Simple_Callable_Node_Traverser $simple_callable_node_traverser, Php_Version_Provider $php_version_provider)
    {
        $this->builder_factory = $builder_factory;
        $this->php_doc_info_factory = $php_doc_info_factory;
        $this->static_type_mapper = $static_type_mapper;
        $this->property_type_decorator = $property_type_decorator;
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
        $this->php_version_provider = $php_version_provider;
    }
    /**
     * @param string|ObjectReference::* $className
     * Creates "\SomeClass::CONSTANT"
     */
    public function create_class_const_fetch(string $class_name, string $constant_name): Class_Const_Fetch
    {
        $name = $this->create_name($class_name);
        return $this->create_class_const_fetch_from_name($name, $constant_name);
    }
    /**
     * @param string|ObjectReference::* $className
     * Creates "\SomeClass::class"
     */
    public function create_class_const_reference(string $class_name): Class_Const_Fetch
    {
        return $this->create_class_const_fetch($class_name, 'class');
    }
    /**
     * Creates "['item', $variable]"
     *
     * @param mixed[] $items
     */
    public function create_array(array $items): Array_
    {
        $array_items = [];
        $default_key = 0;
        foreach ($items as $key => $item) {
            $custom_key = $key !== $default_key ? $key : null;
            $array_items[] = $this->create_array_item($item, $custom_key);
            ++$default_key;
        }
        return new Array_($array_items);
    }
    /**
     * Creates "($args)"
     *
     * @param mixed[] $values
     * @return Arg[]
     */
    public function create_args(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($value instanceof Array_Item) {
                $values[$key] = $value->value;
            }
        }
        return $this->builder_factory->args($values);
    }
    public function create_declares_strict_type(): Declare_
    {
        $declare_item = new Declare_Item(new Identifier('strict_types'), new Int_(1));
        return new Declare_([$declare_item]);
    }
    /**
     * Creates $this->property = $property;
     */
    public function create_property_assignment(string $property_name): Assign
    {
        $variable = new Variable($property_name);
        return $this->create_property_assignment_with_expr($property_name, $variable);
    }
    /**
     * @api
     */
    public function create_property_assignment_with_expr(string $property_name, Expr $expr): Assign
    {
        $property_fetch = $this->create_property_fetch(self::THIS, $property_name);
        return new Assign($property_fetch, $expr);
    }
    /**
     * @param mixed $argument
     */
    public function create_arg($argument): Arg
    {
        return new Arg(Builder_Helpers::normalize_value($argument));
    }
    public function create_public_method(string $name): Class_Method
    {
        $method = new Method($name);
        $method->make_public();
        return $method->get_node();
    }
    public function create_param_from_name_and_type(string $name, ?Type $type): Param
    {
        $param = new Param_Builder($name);
        if ($type instanceof Type) {
            $type_node = $this->static_type_mapper->map_php_stan_type_to_php_parser_node($type, Type_Kind::PARAM);
            if ($type_node instanceof Node) {
                $param->set_type($type_node);
            }
        }
        return $param->get_node();
    }
    public function create_private_property_from_name_and_type(string $name, ?Type $type): Property
    {
        $property_builder = new Property_Builder($name);
        $property_builder->make_private();
        $property = $property_builder->get_node();
        $this->property_type_decorator->decorate($property, $type);
        return $property;
    }
    /**
     * @api symfony
     * @param mixed[] $arguments
     */
    public function create_local_method_call(string $method, array $arguments = []): Method_Call
    {
        $variable = new Variable('this');
        return $this->create_method_call($variable, $method, $arguments);
    }
    /**
     * @param mixed[] $arguments
     * @param \PhpParser\Node\Expr|string $exprOrVariableName
     */
    public function create_method_call($expr_or_variable_name, string $method, array $arguments = []): Method_Call
    {
        $caller_expr = $this->create_method_caller($expr_or_variable_name);
        return $this->builder_factory->method_call($caller_expr, $method, $arguments);
    }
    /**
     * @param string|\PhpParser\Node\Expr $variableNameOrExpr
     */
    public function create_property_fetch($variable_name_or_expr, string $property): Property_Fetch
    {
        $fetcher_expr = is_string($variable_name_or_expr) ? new Variable($variable_name_or_expr) : $variable_name_or_expr;
        return $this->builder_factory->property_fetch($fetcher_expr, $property);
    }
    /**
     * @api doctrine
     */
    public function create_private_property(string $name): Property
    {
        $property_builder = new Property_Builder($name);
        $property_builder->make_private();
        $property = $property_builder->get_node();
        $this->php_doc_info_factory->create_from_node($property);
        return $property;
    }
    /**
     * @param Expr[] $exprs
     */
    public function create_concat(array $exprs): ?Concat
    {
        if (count($exprs) < 2) {
            return null;
        }
        $previous_concat = array_shift($exprs);
        foreach ($exprs as $expr) {
            $previous_concat = new Concat($previous_concat, $expr);
        }
        if (!$previous_concat instanceof Concat) {
            throw new Should_Not_Happen_Exception();
        }
        return $previous_concat;
    }
    /**
     * @param string|ObjectReference::* $class
     * @param Node[] $args
     */
    public function create_static_call(string $class, string $method, array $args = []): Static_Call
    {
        $name = $this->create_name($class);
        $args = $this->create_args($args);
        return new Static_Call($name, $method, $args);
    }
    /**
     * @param mixed[] $arguments
     */
    public function create_func_call(string $name, array $arguments = []): Func_Call
    {
        $arguments = $this->create_args($arguments);
        return new Func_Call(new Name($name), $arguments);
    }
    public function create_self_fetch_constant(string $constant_name): Class_Const_Fetch
    {
        $name = new Name(Object_Reference::SELF);
        return new Class_Const_Fetch($name, $constant_name);
    }
    public function create_null(): Const_Fetch
    {
        return new Const_Fetch(new Name('null'));
    }
    public function create_promoted_property_param(Property_Metadata $property_metadata): Param
    {
        $param_builder = new Param_Builder($property_metadata->get_name());
        $property_type = $property_metadata->get_type();
        if ($property_type instanceof Type) {
            $type_node = $this->static_type_mapper->map_php_stan_type_to_php_parser_node($property_type, Type_Kind::PROPERTY);
            if ($type_node instanceof Node) {
                $param_builder->set_type($type_node);
            }
        }
        $param = $param_builder->get_node();
        $property_flags = $property_metadata->get_flags();
        $param->flags = $property_flags !== 0 ? $property_flags : Modifiers::PRIVATE;
        // make readonly by default
        if ($this->php_version_provider->is_at_least_php_version(Php_Version_Feature::READONLY_PROPERTY)) {
            $param->flags |= Modifiers::READONLY;
        }
        return $param;
    }
    public function create_false(): Const_Fetch
    {
        return new Const_Fetch(new Name('false'));
    }
    public function create_true(): Const_Fetch
    {
        return new Const_Fetch(new Name('true'));
    }
    /**
     * @api phpunit
     * @param string|ObjectReference::* $constantName
     */
    public function create_class_const_fetch_from_name(Name $class_name, string $constant_name): Class_Const_Fetch
    {
        return $this->builder_factory->class_const_fetch($class_name, $constant_name);
    }
    /**
     * @param array<NotIdentical|BooleanAnd|BooleanOr|Identical> $newNodes
     */
    public function create_return_boolean_and(array $new_nodes): ?Expr
    {
        if ($new_nodes === []) {
            return null;
        }
        if (count($new_nodes) === 1) {
            return $new_nodes[0];
        }
        return $this->create_boolean_and_from_nodes($new_nodes);
    }
    /**
     * Setting all child nodes to null is needed to avoid reprint of invalid tokens
     * @see https://github.com/rectorphp/rector/issues/8712
     *
     * @template TNode as Node
     *
     * @param TNode $node
     * @return TNode
     */
    public function create_reprinted_node(Node $node): Node
    {
        // reset original node, to allow the printer to re-use the node
        $node->set_attribute(Attribute_Key::ORIGINAL_NODE, null);
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($node, static function (Node $sub_node): Node {
            $sub_node->set_attribute(Attribute_Key::ORIGINAL_NODE, null);
            return $sub_node;
        });
        return $node;
    }
    /**
     * @param string|int|null $key
     * @param mixed $item
     */
    private function create_array_item($item, $key = null): Array_Item
    {
        $array_item = null;
        if ($item instanceof Variable || $item instanceof Method_Call || $item instanceof Static_Call || $item instanceof Func_Call || $item instanceof Concat || $item instanceof Scalar || $item instanceof Cast || $item instanceof Const_Fetch || $item instanceof Property_Fetch || $item instanceof Static_Property_Fetch || $item instanceof Nullsafe_Property_Fetch || $item instanceof Nullsafe_Method_Call || $item instanceof Clone_ || $item instanceof Instanceof_) {
            $array_item = new Array_Item($item);
        } elseif ($item instanceof Identifier) {
            $string = new String_($item->to_string());
            $array_item = new Array_Item($string);
        } elseif (is_scalar($item) || $item instanceof Array_) {
            $item_value = Builder_Helpers::normalize_value($item);
            $array_item = new Array_Item($item_value);
        } elseif (is_array($item)) {
            $array_item = new Array_Item($this->create_array($item));
        } elseif ($item === null || $item instanceof Class_Const_Fetch) {
            $item_value = Builder_Helpers::normalize_value($item);
            $array_item = new Array_Item($item_value);
        } elseif ($item instanceof Arg) {
            $array_item = new Array_Item($item->value);
        }
        if ($array_item instanceof Array_Item) {
            $this->decorate_array_item_with_key($key, $array_item);
            return $array_item;
        }
        if ($item instanceof New_) {
            $array_item = new Array_Item($item);
            $this->decorate_array_item_with_key($key, $array_item);
            return $array_item;
        }
        if ($item instanceof Unary_Plus || $item instanceof Unary_Minus) {
            $array_item = new Array_Item($item);
            $this->decorate_array_item_with_key($key, $array_item);
            return $array_item;
        }
        // fallback to other nodes
        if ($item instanceof Expr) {
            $array_item = new Array_Item($item);
            $this->decorate_array_item_with_key($key, $array_item);
            return $array_item;
        }
        $item_value = Builder_Helpers::normalize_value($item);
        $array_item = new Array_Item($item_value);
        $this->decorate_array_item_with_key($key, $array_item);
        return $array_item;
    }
    /**
     * @param int|string|null $key
     */
    private function decorate_array_item_with_key($key, Array_Item $array_item): void
    {
        if ($key === null) {
            return;
        }
        $array_item->key = Builder_Helpers::normalize_value($key);
    }
    /**
     * @param Expr\BinaryOp[] $binaryOps
     */
    private function create_boolean_and_from_nodes(array $binary_ops): Boolean_And
    {
        /** @var NotIdentical|BooleanAnd $mainBooleanAnd */
        $main_boolean_and = array_shift($binary_ops);
        foreach ($binary_ops as $binary_op) {
            $main_boolean_and = new Boolean_And($main_boolean_and, $binary_op);
        }
        /** @var BooleanAnd $mainBooleanAnd */
        return $main_boolean_and;
    }
    /**
     * @param string|ObjectReference::* $className
     * @return \PhpParser\Node\Name|\PhpParser\Node\Name\FullyQualified
     */
    private function create_name(string $class_name)
    {
        if (in_array($class_name, [Object_Reference::PARENT, Object_Reference::SELF, Object_Reference::STATIC], \true)) {
            return new Name($class_name);
        }
        return new Fully_Qualified($class_name);
    }
    /**
     * @param \PhpParser\Node\Expr|string $exprOrVariableName
     * @return \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\Variable|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticPropertyFetch|\PhpParser\Node\Expr
     */
    private function create_method_caller($expr_or_variable_name)
    {
        if (is_string($expr_or_variable_name)) {
            return new Variable($expr_or_variable_name);
        }
        if ($expr_or_variable_name instanceof Property_Fetch) {
            return new Property_Fetch($expr_or_variable_name->var, $expr_or_variable_name->name);
        }
        if ($expr_or_variable_name instanceof Static_Property_Fetch) {
            return new Static_Property_Fetch($expr_or_variable_name->class, $expr_or_variable_name->name);
        }
        if ($expr_or_variable_name instanceof Method_Call) {
            return new Method_Call($expr_or_variable_name->var, $expr_or_variable_name->name, $expr_or_variable_name->args);
        }
        return $expr_or_variable_name;
    }
}