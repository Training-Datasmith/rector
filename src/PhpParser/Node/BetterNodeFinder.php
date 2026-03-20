<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Arrow_Function;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Expr\Yield_;
use Php_Parser\Node\Expr\Yield_From;
use Php_Parser\Node\Function_Like;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Return_;
use Php_Parser\Node_Finder;
use Php_Parser\Node_Visitor;
use Rector\Node_Analyzer\Class_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\PhpParser\Node\BetterNodeFinder\BetterNodeFinderTest
 */
final class Better_Node_Finder
{
    /**
     * @readonly
     */
    private Node_Finder $node_finder;
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
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    public function __construct(Node_Finder $node_finder, Node_Name_Resolver $node_name_resolver, Class_Analyzer $class_analyzer, Simple_Callable_Node_Traverser $simple_callable_node_traverser)
    {
        $this->node_finder = $node_finder;
        $this->node_name_resolver = $node_name_resolver;
        $this->class_analyzer = $class_analyzer;
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
    }
    /**
     * @template T of Node
     * @param array<class-string<T>> $types
     * @param Node|Node[]|Stmt[] $nodes
     * @return T[]
     */
    public function find_instances_of($nodes, array $types): array
    {
        $found_instances = [];
        foreach ($types as $type) {
            $current_found_instances = $this->find_instance_of($nodes, $type);
            $found_instances = array_merge($found_instances, $current_found_instances);
        }
        return $found_instances;
    }
    /**
     * @template T of Node
     * @param class-string<T> $type
     * @param Node|Node[]|Stmt[] $nodes
     * @return T[]
     */
    public function find_instance_of($nodes, string $type): array
    {
        return $this->node_finder->find_instance_of($nodes, $type);
    }
    /**
     * @template T of Node
     * @param class-string<T> $type
     * @param Node|Node[] $nodes
     *
     * @return T|null
     */
    public function find_first_instance_of($nodes, string $type): ?Node
    {
        Assert::is_a_of($type, Node::class);
        return $this->node_finder->find_first_instance_of($nodes, $type);
    }
    /**
     * @param class-string<Node> $type
     * @param Node[] $nodes
     */
    public function has_instance_of_name(array $nodes, string $type, string $name): bool
    {
        Assert::is_a_of($type, Node::class);
        return (bool) $this->find_instance_of_name($nodes, $type, $name);
    }
    /**
     * @param Node[] $nodes
     */
    public function has_variable_of_name(array $nodes, string $name): bool
    {
        return $this->find_variable_of_name($nodes, $name) instanceof Node;
    }
    /**
     * @api
     * @param Node|Node[] $nodes
     * @return Variable|null
     */
    public function find_variable_of_name($nodes, string $name): ?Node
    {
        return $this->find_instance_of_name($nodes, Variable::class, $name);
    }
    /**
     * @param Node|Node[] $nodes
     * @param array<class-string<Node>> $types
     */
    public function has_instances_of($nodes, array $types): bool
    {
        Assert::all_is_a_of($types, Node::class);
        return (bool) $this->node_finder->find_first($nodes, static function (Node $node) use ($types): bool {
            foreach ($types as $type) {
                if ($node instanceof $type) {
                    return \true;
                }
            }
            return \false;
        });
    }
    /**
     * @param Node|Node[] $nodes
     * @param callable(Node $node): bool $filter
     * @return Node[]
     */
    public function find($nodes, callable $filter): array
    {
        return $this->node_finder->find($nodes, $filter);
    }
    /**
     * @api symfony
     * @param Node[] $nodes
     * @return Class_|null
     */
    public function find_first_non_anonymous_class(array $nodes): ?Node
    {
        // skip anonymous classes
        return $this->find_first($nodes, fn(Node $node): bool => $node instanceof Class_ && !$this->class_analyzer->is_anonymous_class($node));
    }
    /**
     * @param Node|Node[] $nodes
     * @param callable(Node $filter): bool $filter
     */
    public function find_first($nodes, callable $filter): ?Node
    {
        return $this->node_finder->find_first($nodes, $filter);
    }
    /**
     * @template T of Node
     * @param array<class-string<T>>|class-string<T> $types
     */
    public function has_instances_of_in_function_like_scoped(Function_Like $function_like, $types): bool
    {
        if (is_string($types)) {
            $types = [$types];
        }
        $is_found_node = \false;
        $this->simple_callable_node_traverser->traverse_nodes_with_callable((array) $function_like->get_stmts(), static function (Node $sub_node) use ($types, &$is_found_node): ?int {
            if ($sub_node instanceof Class_ || $sub_node instanceof Function_Like) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            foreach ($types as $type) {
                if ($sub_node instanceof $type) {
                    $is_found_node = \true;
                    return Node_Visitor::STOP_TRAVERSAL;
                }
            }
            return null;
        });
        return $is_found_node;
    }
    /**
     * @return Return_[]
     */
    public function find_returns_scoped(Function_Like $function_like): array
    {
        $returns = [];
        $this->simple_callable_node_traverser->traverse_nodes_with_callable((array) $function_like->get_stmts(), function (Node $sub_node) use (&$returns): ?int {
            if ($sub_node instanceof Class_ || $sub_node instanceof Function_Like) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($sub_node instanceof Yield_ || $sub_node instanceof Yield_From) {
                $returns = [];
                return Node_Visitor::STOP_TRAVERSAL;
            }
            if ($sub_node instanceof Return_) {
                $returns[] = $sub_node;
            }
            return null;
        });
        return $returns;
    }
    /**
     * @api to be used
     *
     * @template T of Node
     * @param Node[] $nodes
     * @param class-string<T>|array<class-string<T>> $types
     * @return T[]
     */
    public function find_instances_of_scoped(array $nodes, $types): array
    {
        // here verify only pass single nodes as FunctionLike
        if (count($nodes) === 1 && $nodes[0] instanceof Function_Like) {
            $nodes = (array) $nodes[0]->get_stmts();
        }
        if (is_string($types)) {
            $types = [$types];
        }
        /** @var T[] $foundNodes */
        $found_nodes = [];
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($nodes, static function (Node $sub_node) use ($types, &$found_nodes): ?int {
            if ($sub_node instanceof Class_ || $sub_node instanceof Function_Like && !$sub_node instanceof Class_Method && !$sub_node instanceof Arrow_Function) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            foreach ($types as $type) {
                if ($sub_node instanceof $type) {
                    $found_nodes[] = $sub_node;
                    return null;
                }
            }
            return null;
        });
        return $found_nodes;
    }
    /**
     * @template T of Node
     * @param array<class-string<T>>|class-string<T> $types
     * @return array<T>
     */
    public function find_instances_of_in_function_like_scoped(Function_Like $function_like, $types): array
    {
        return $this->find_instances_of_scoped([$function_like], $types);
    }
    /**
     * @param callable(Node $node): bool $filter
     */
    public function find_first_in_function_like_scoped(Function_Like $function_like, callable $filter): ?Node
    {
        $scoped_node = null;
        $this->simple_callable_node_traverser->traverse_nodes_with_callable((array) $function_like->get_stmts(), function (Node $sub_node) use (&$scoped_node, $filter): ?int {
            if (!$filter($sub_node)) {
                if ($sub_node instanceof Class_ || $sub_node instanceof Function_Like) {
                    return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                }
                return null;
            }
            $scoped_node = $sub_node;
            return Node_Visitor::STOP_TRAVERSAL;
        });
        return $scoped_node;
    }
    /**
     * @template T of Node
     * @param Node|Node[] $nodes
     * @param class-string<T> $type
     */
    private function find_instance_of_name($nodes, string $type, string $name): ?Node
    {
        Assert::is_a_of($type, Node::class);
        return $this->node_finder->find_first($nodes, fn(Node $node): bool => $node instanceof $type && $this->node_name_resolver->is_name($node, $name));
    }
}