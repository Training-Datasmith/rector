<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan\Scope;

use Error;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Array_Dim_Fetch;
use Php_Parser\Node\Expr\Arrow_Function;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Assign_Op;
use Php_Parser\Node\Expr\Assign_Ref;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Bitwise_Not;
use Php_Parser\Node\Expr\Boolean_Not;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Cast;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Expr\Clone_;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Expr\Const_Fetch;
use Php_Parser\Node\Expr\Empty_;
use Php_Parser\Node\Expr\Error_Suppress;
use Php_Parser\Node\Expr\Eval_;
use Php_Parser\Node\Expr\Exit_;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Include_;
use Php_Parser\Node\Expr\Instanceof_;
use Php_Parser\Node\Expr\Isset_;
use Php_Parser\Node\Expr\List_;
use Php_Parser\Node\Expr\Match_;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Expr\Post_Dec;
use Php_Parser\Node\Expr\Post_Inc;
use Php_Parser\Node\Expr\Pre_Dec;
use Php_Parser\Node\Expr\Pre_Inc;
use Php_Parser\Node\Expr\Print_;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Expr\Ternary;
use Php_Parser\Node\Expr\Throw_;
use Php_Parser\Node\Expr\Unary_Minus;
use Php_Parser\Node\Expr\Unary_Plus;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Expr\Yield_;
use Php_Parser\Node\Expr\Yield_From;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Intersection_Type;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Nullable_Type;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Catch_;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Do_;
use Php_Parser\Node\Stmt\Echo_;
use Php_Parser\Node\Stmt\Else_If_;
use Php_Parser\Node\Stmt\Enum_;
use Php_Parser\Node\Stmt\Enum_Case;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Finally_;
use Php_Parser\Node\Stmt\For_;
use Php_Parser\Node\Stmt\Foreach_;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Return_;
use Php_Parser\Node\Stmt\Switch_;
use Php_Parser\Node\Stmt\Trait_;
use Php_Parser\Node\Stmt\Try_Catch;
use Php_Parser\Node\Stmt\Unset_;
use Php_Parser\Node\Stmt\While_;
use Php_Parser\Node\Union_Type;
use Php_Parser\Node_Traverser;
use Php_Stan\Analyser\Fiber\Fiber_Scope;
use Php_Stan\Analyser\Mutating_Scope;
use Php_Stan\Analyser\Node_Scope_Resolver;
use Php_Stan\Analyser\Scope_Context;
use Php_Stan\Analyser\Undefined_Variable_Exception;
use Php_Stan\Node\Function_Callable_Node;
use Php_Stan\Node\Instantiation_Callable_Node;
use Php_Stan\Node\Method_Callable_Node;
use Php_Stan\Node\Printer\Printer;
use Php_Stan\Node\Static_Method_Callable_Node;
use Php_Stan\Node\Unreachable_Statement_Node;
use Php_Stan\Node\Virtual_Node;
use Php_Stan\Parser\Parser_Errors_Exception;
use Php_Stan\Php_Doc_Parser\Parser\Parser_Exception;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Should_Not_Happen_Exception;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type_Combinator;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Analyzer\Class_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Node\File_Node;
use Rector\Util\Reflection\Privates_Accessor;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @inspired by https://github.com/silverstripe/silverstripe-upgrader/blob/532182b23e854d02e0b27e68ebc394f436de0682/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php
 * - https://github.com/silverstripe/silverstripe-upgrader/pull/57/commits/e5c7cfa166ad940d9d4ff69537d9f7608e992359#diff-5e0807bb3dc03d6a8d8b6ad049abd774
 */
final class Php_Stan_Node_Scope_Resolver
{
    /**
     * @readonly
     */
    private Node_Scope_Resolver $node_scope_resolver;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private \Rector\Node_Type_Resolver\Php_Stan\Scope\Scope_Factory $scope_factory;
    /**
     * @readonly
     */
    private Privates_Accessor $privates_accessor;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Class_Analyzer $class_analyzer;
    /**
     * @var string
     */
    private const CONTEXT = 'context';
    /**
     * @readonly
     */
    private Node_Traverser $node_traverser;
    /**
     * @param DecoratingNodeVisitorInterface[] $decoratingNodeVisitors
     */
    public function __construct(Node_Scope_Resolver $node_scope_resolver, Reflection_Provider $reflection_provider, iterable $decorating_node_visitors, \Rector\Node_Type_Resolver\Php_Stan\Scope\Scope_Factory $scope_factory, Privates_Accessor $privates_accessor, Node_Name_Resolver $node_name_resolver, Class_Analyzer $class_analyzer)
    {
        $this->node_scope_resolver = $node_scope_resolver;
        $this->reflection_provider = $reflection_provider;
        $this->scope_factory = $scope_factory;
        $this->privates_accessor = $privates_accessor;
        $this->node_name_resolver = $node_name_resolver;
        $this->class_analyzer = $class_analyzer;
        // @todo make use of immutable, to avoid tedious traversing
        $this->node_traverser = new Node_Traverser(...$decorating_node_visitors);
    }
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function process_nodes(array $stmts, string $file_path, ?Mutating_Scope $former_mutating_scope = null): array
    {
        /**
         * The stmts must be array of Stmt, or it will be silently skipped by PHPStan
         * @see vendor/phpstan/phpstan/phpstan.phar/src/Analyser/NodeScopeResolver.php:282
         */
        Assert::all_is_instance_of($stmts, Stmt::class);
        $scope = $former_mutating_scope ?? $this->scope_factory->create_from_file($file_path);
        $node_callback = function (Node $node, Mutating_Scope $mutating_scope) use (&$node_callback, $file_path): void {
            if ($mutating_scope instanceof Fiber_Scope) {
                $mutating_scope = $mutating_scope->to_mutating_scope();
            }
            // the class reflection is resolved AFTER entering to class node
            // so we need to get it from the first after this one
            if ($node instanceof Class_ || $node instanceof Interface_ || $node instanceof Enum_) {
                /** @var MutatingScope $mutatingScope */
                $mutating_scope = $this->resolve_class_or_interface_scope($node, $mutating_scope);
                $node->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                if ($node instanceof Class_) {
                    if ($node->extends instanceof Fully_Qualified) {
                        $node->extends->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                    }
                    foreach ($node->implements as $implement) {
                        $implement->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                    }
                }
                return;
            }
            if ($node instanceof Trait_) {
                $this->process_trait($node, $mutating_scope, $node_callback);
                return;
            }
            // special case for unreachable nodes
            // early check here as UnreachableStatementNode is special VirtualNode
            // so node to be checked inside
            if ($node instanceof Unreachable_Statement_Node) {
                $this->process_unreachable_statement_node($node, $mutating_scope, $node_callback);
                return;
            }
            // init current Node set Attribute
            // not a VirtualNode, then set scope attribute
            // do not return early, as its properties will be checked next
            if (!$node instanceof Virtual_Node) {
                $node->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
            }
            // handle unwrapped stmts
            if ($node instanceof File_Node) {
                $this->node_scope_resolver_process_nodes($node->stmts, $mutating_scope, $node_callback);
                return;
            }
            $this->decorate_node_attr_groups($node, $mutating_scope, $node_callback);
            if (($node instanceof Expression || $node instanceof Return_ || $node instanceof Enum_Case || $node instanceof Cast || $node instanceof Yield_From || $node instanceof Unary_Minus || $node instanceof Unary_Plus || $node instanceof Throw_ || $node instanceof Empty_ || $node instanceof Boolean_Not || $node instanceof Clone_ || $node instanceof Error_Suppress || $node instanceof Bitwise_Not || $node instanceof Eval_ || $node instanceof Print_ || $node instanceof Exit_ || $node instanceof Arrow_Function || $node instanceof Include_ || $node instanceof Instanceof_) && $node->expr instanceof Expr) {
                $node->expr->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Post_Inc || $node instanceof Post_Dec || $node instanceof Pre_Inc || $node instanceof Pre_Dec) {
                $node->var->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Array_Dim_Fetch) {
                $node->var->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                if ($node->dim instanceof Expr) {
                    $node->dim->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                }
                return;
            }
            if ($node instanceof Assign || $node instanceof Assign_Op || $node instanceof Assign_Ref) {
                $this->process_assign($node, $mutating_scope);
                if ($node->var instanceof Variable && $node->var->name instanceof Expr) {
                    $this->node_scope_resolver_process_nodes([new Expression($node->var), new Expression($node->expr)], $mutating_scope, $node_callback);
                }
                return;
            }
            if ($node instanceof Ternary) {
                $this->process_ternary($node, $mutating_scope);
                return;
            }
            if ($node instanceof Binary_Op) {
                $this->process_binary_op($node, $mutating_scope);
                return;
            }
            if ($node instanceof Arg) {
                $node->value->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Foreach_) {
                // decorate value as well
                $node->value_var->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                if ($node->value_var instanceof List_) {
                    $this->process_array($node->value_var, $mutating_scope);
                }
                return;
            }
            if ($node instanceof For_) {
                foreach (array_merge($node->init, $node->cond, $node->loop) as $expr) {
                    $expr->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                    if ($expr instanceof Binary_Op) {
                        $this->process_binary_op($expr, $mutating_scope);
                    }
                    if ($expr instanceof Assign) {
                        $this->process_assign($expr, $mutating_scope);
                    }
                }
                return;
            }
            if ($node instanceof Array_) {
                $this->process_array($node, $mutating_scope);
                return;
            }
            if ($node instanceof Property) {
                $this->process_property($node, $mutating_scope, $node_callback);
                return;
            }
            if ($node instanceof Switch_) {
                $this->process_switch($node, $mutating_scope);
                return;
            }
            if ($node instanceof Try_Catch) {
                $this->process_try_catch($node, $mutating_scope);
                return;
            }
            if ($node instanceof Catch_) {
                $this->process_catch($node, $file_path, $mutating_scope);
                return;
            }
            if ($node instanceof Nullable_Type) {
                $node->type->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Union_Type || $node instanceof Intersection_Type) {
                foreach ($node->types as $type) {
                    $type->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                }
                return;
            }
            if ($node instanceof Static_Property_Fetch || $node instanceof Class_Const_Fetch) {
                $node->class->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                $node->name->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Property_Fetch) {
                $node->var->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                $node->name->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Const_Fetch) {
                $node->name->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Call_Like) {
                $this->process_call_like($node, $mutating_scope);
                return;
            }
            if ($node instanceof Match_) {
                $this->process_match($node, $mutating_scope);
                return;
            }
            if ($node instanceof Yield_) {
                $this->process_yield($node, $mutating_scope);
                return;
            }
            if ($node instanceof Isset_ || $node instanceof Unset_) {
                $this->process_isset_or_unset($node, $mutating_scope);
                return;
            }
            if ($node instanceof Echo_) {
                $this->process_echo($node, $mutating_scope);
                return;
            }
            if ($node instanceof If_ || $node instanceof Else_If_ || $node instanceof Do_ || $node instanceof While_) {
                $node->cond->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                return;
            }
            if ($node instanceof Method_Callable_Node || $node instanceof Function_Callable_Node || $node instanceof Static_Method_Callable_Node || $node instanceof Instantiation_Callable_Node) {
                $node->get_original_node()->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                $this->process_call_like($node->get_original_node(), $mutating_scope);
                return;
            }
        };
        try {
            $this->node_scope_resolver_process_nodes($stmts, $scope, $node_callback);
        } catch (Error $error) {
            if (strncmp($error->get_message(), 'Call to undefined method ' . Printer::class . '::pPHPStan_', strlen('Call to undefined method ' . Printer::class . '::pPHPStan_')) !== 0) {
                throw $error;
            }
            // nothing we can do more precise here as error printing from deep internal PHPStan Printer service with service injection we cannot reset
            // in the middle of process
            // fallback to fill by found scope
            \Rector\Node_Type_Resolver\Php_Stan\Scope\Rector_Node_Scope_Resolver::process_nodes($stmts, $scope);
        }
        // use after scope filling so DecoratingNodeVisitorInterface instance can fetch the scope of target node
        // @see https://github.com/rectorphp/rector-src/pull/7721#discussion_r2595932460
        $this->node_traverser->traverse($stmts);
        return $stmts;
    }
    private function process_yield(Yield_ $yield, Mutating_Scope $mutating_scope): void
    {
        if ($yield->key instanceof Expr) {
            $yield->key->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
        if ($yield->value instanceof Expr) {
            $yield->value->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
    }
    /**
     * @param \PhpParser\Node\Expr\Isset_|\PhpParser\Node\Stmt\Unset_ $node
     */
    private function process_isset_or_unset($node, Mutating_Scope $mutating_scope): void
    {
        foreach ($node->vars as $var) {
            $var->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
    }
    private function process_echo(Echo_ $echo, Mutating_Scope $mutating_scope): void
    {
        foreach ($echo->exprs as $expr) {
            $expr->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
    }
    private function process_match(Match_ $match, Mutating_Scope $mutating_scope): void
    {
        $match->cond->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        foreach ($match->arms as $arm) {
            if ($arm->conds !== null) {
                foreach ($arm->conds as $cond) {
                    $cond->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
                }
            }
            $arm->body->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
    }
    /**
     * @param Stmt[] $stmts
     * @param callable(Node $node, MutatingScope $scope): void $nodeCallback
     */
    private function node_scope_resolver_process_nodes(array $stmts, Mutating_Scope $mutating_scope, callable $node_callback): void
    {
        try {
            $this->node_scope_resolver->process_nodes($stmts, $mutating_scope, $node_callback);
        } catch (Parser_Errors_Exception|Parser_Exception|Should_Not_Happen_Exception|Undefined_Variable_Exception $exception) {
            // nothing we can do more precise here as error parsing from deep internal PHPStan service with service injection we cannot reset
            // in the middle of process
            // fallback to fill by found scope
            \Rector\Node_Type_Resolver\Php_Stan\Scope\Rector_Node_Scope_Resolver::process_nodes($stmts, $mutating_scope);
        }
    }
    private function process_call_like(Call_Like $call_like, Mutating_Scope $mutating_scope): void
    {
        if ($call_like instanceof Static_Call) {
            $call_like->class->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
            $call_like->name->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        } elseif ($call_like instanceof Method_Call || $call_like instanceof Nullsafe_Method_Call) {
            $call_like->var->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
            $call_like->name->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        } elseif ($call_like instanceof Func_Call) {
            $call_like->name->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        } elseif ($call_like instanceof New_ && !$call_like->class instanceof Class_) {
            $call_like->class->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
    }
    /**
     * @param \PhpParser\Node\Expr\Assign|\PhpParser\Node\Expr\AssignOp|\PhpParser\Node\Expr\AssignRef $assign
     */
    private function process_assign($assign, Mutating_Scope $mutating_scope): void
    {
        $assign->var->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        $assign->expr->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
    }
    /**
     * @param \PhpParser\Node\Expr\List_|\PhpParser\Node\Expr\Array_ $array
     */
    private function process_array($array, Mutating_Scope $mutating_scope): void
    {
        foreach ($array->items as $array_item) {
            if (!$array_item instanceof Array_Item) {
                continue;
            }
            $this->process_array_item($array_item, $mutating_scope);
        }
    }
    private function process_array_item(Array_Item $array_item, Mutating_Scope $mutating_scope): void
    {
        $array_item->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        if ($array_item->key instanceof Expr) {
            $array_item->key->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
        $array_item->value->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        if ($array_item->value instanceof List_) {
            $this->process_array($array_item->value, $mutating_scope);
        }
    }
    /**
     * @param callable(Node $trait, MutatingScope $scope): void $nodeCallback
     */
    private function decorate_node_attr_groups(Node $node, Mutating_Scope $mutating_scope, callable $node_callback): void
    {
        // better to have AttrGroupsAwareInterface for all Node definition with attrGroups property
        // but because may conflict with StmtsAwareInterface patch, this needs to be here
        if (!$node instanceof Param && !$node instanceof Arrow_Function && !$node instanceof Closure && !$node instanceof Class_Const && !$node instanceof Class_Like && !$node instanceof Class_Method && !$node instanceof Enum_Case && !$node instanceof Function_ && !$node instanceof Property) {
            return;
        }
        foreach ($node->attr_groups as $attr_group) {
            foreach ($attr_group->attrs as $attr) {
                foreach ($attr->args as $arg) {
                    $this->node_scope_resolver_process_nodes([new Expression($arg->value)], $mutating_scope, $node_callback);
                }
            }
        }
    }
    private function process_switch(Switch_ $switch, Mutating_Scope $mutating_scope): void
    {
        $switch->cond->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        // decorate value as well
        foreach ($switch->cases as $case) {
            $case->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
    }
    private function process_catch(Catch_ $catch, string $file_path, Mutating_Scope $mutating_scope): void
    {
        $var_name = $catch->var instanceof Variable ? $this->node_name_resolver->get_name($catch->var) : null;
        $type = Type_Combinator::union(...array_map(static fn(Name $name): Object_Type => new Object_Type((string) $name), $catch->types));
        $catch_mutating_scope = $mutating_scope->enter_catch_type($type, $var_name);
        $this->process_nodes($catch->stmts, $file_path, $catch_mutating_scope);
    }
    private function process_try_catch(Try_Catch $try_catch, Mutating_Scope $mutating_scope): void
    {
        if ($try_catch->finally instanceof Finally_) {
            $try_catch->finally->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
    }
    /**
     * @param callable(Node $node, MutatingScope $scope): void $nodeCallback
     */
    private function process_unreachable_statement_node(Unreachable_Statement_Node $unreachable_statement_node, Mutating_Scope $mutating_scope, callable $node_callback): void
    {
        $original_stmt = $unreachable_statement_node->get_original_statement();
        $this->node_scope_resolver_process_nodes(array_merge([$original_stmt], $unreachable_statement_node->get_next_statements()), $mutating_scope, $node_callback);
    }
    /**
     * @param callable(Node $node, MutatingScope $scope): void $nodeCallback
     */
    private function process_property(Property $property, Mutating_Scope $mutating_scope, callable $node_callback): void
    {
        foreach ($property->props as $property_property) {
            $property_property->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
            if ($property_property->default instanceof Expr) {
                $property_property->default->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
            }
        }
        foreach ($property->hooks as $hook) {
            if ($hook->body === null) {
                continue;
            }
            /** @var Stmt[] $stmts */
            $stmts = $hook->body instanceof Expr ? [new Expression($hook->body)] : [$hook->body];
            $this->node_scope_resolver_process_nodes($stmts, $mutating_scope, $node_callback);
        }
    }
    private function process_binary_op(Binary_Op $binary_op, Mutating_Scope $mutating_scope): void
    {
        $binary_op->left->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        $binary_op->right->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
    }
    private function process_ternary(Ternary $ternary, Mutating_Scope $mutating_scope): void
    {
        if ($ternary->if instanceof Expr) {
            $ternary->if->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
        }
        $ternary->else->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
    }
    /**
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Stmt\Enum_ $classLike
     */
    private function resolve_class_or_interface_scope($class_like, Mutating_Scope $mutating_scope): Mutating_Scope
    {
        $is_anonymous = $this->class_analyzer->is_anonymous_class($class_like);
        // is anonymous class? - not possible to enter it since PHPStan 0.12.33, see https://github.com/phpstan/phpstan-src/commit/e87fb0ec26f9c8552bbeef26a868b1e5d8185e91
        if ($class_like instanceof Class_ && $is_anonymous) {
            $class_reflection = $this->reflection_provider->get_anonymous_class_reflection($class_like, $mutating_scope);
        } else {
            $class_name = $this->resolve_class_name($class_like);
            if (!$this->reflection_provider->has_class($class_name)) {
                return $mutating_scope;
            }
            $class_reflection = $this->reflection_provider->get_class($class_name);
        }
        try {
            return $mutating_scope->enter_class($class_reflection);
        } catch (Should_Not_Happen_Exception $exception) {
        }
        $context = $this->privates_accessor->get_private_property($mutating_scope, 'context');
        $this->privates_accessor->set_private_property($context, 'classReflection', null);
        try {
            return $mutating_scope->enter_class($class_reflection);
        } catch (Should_Not_Happen_Exception $exception) {
        }
        return $mutating_scope;
    }
    /**
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Stmt\Trait_|\PhpParser\Node\Stmt\Enum_ $classLike
     */
    private function resolve_class_name($class_like): string
    {
        if ($class_like->namespaced_name instanceof Name) {
            return (string) $class_like->namespaced_name;
        }
        if (!$class_like->name instanceof Identifier) {
            return '';
        }
        return $class_like->name->to_string();
    }
    /**
     * @param callable(Node $trait, MutatingScope $scope): void $nodeCallback
     */
    private function process_trait(Trait_ $trait, Mutating_Scope $mutating_scope, callable $node_callback): void
    {
        $trait_name = $this->resolve_class_name($trait);
        if (!$this->reflection_provider->has_class($trait_name)) {
            $trait->set_attribute(Attribute_Key::SCOPE, $mutating_scope);
            $this->node_scope_resolver_process_nodes($trait->stmts, $mutating_scope, $node_callback);
            $this->decorate_node_attr_groups($trait, $mutating_scope, $node_callback);
            return;
        }
        $trait_class_reflection = $this->reflection_provider->get_class($trait_name);
        $trait_scope = clone $mutating_scope;
        /** @var ScopeContext $scopeContext */
        $scope_context = $this->privates_accessor->get_private_property($trait_scope, self::CONTEXT);
        $trait_context = clone $scope_context;
        // before entering the class/trait again, we have to tell scope no class was set, otherwise it crashes
        $this->privates_accessor->set_private_property($trait_context, 'classReflection', $trait_class_reflection);
        $this->privates_accessor->set_private_property($trait_scope, self::CONTEXT, $trait_context);
        $trait->set_attribute(Attribute_Key::SCOPE, $trait_scope);
        $this->node_scope_resolver_process_nodes($trait->stmts, $trait_scope, $node_callback);
        $this->decorate_node_attr_groups($trait, $trait_scope, $node_callback);
    }
}