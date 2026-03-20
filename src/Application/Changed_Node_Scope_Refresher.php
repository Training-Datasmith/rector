<?php

declare (strict_types=1);
namespace Rector\Application;

use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Closure_Use;
use Php_Parser\Node\Declare_Item;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Param;
use Php_Parser\Node\Property_Item;
use Php_Parser\Node\Static_Var;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Declare_;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Static_;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
use Php_Stan\Analyser\Mutating_Scope;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Analyzer\Scope_Analyzer;
use Rector\Node_Type_Resolver\Php_Stan\Scope\Php_Stan_Node_Scope_Resolver;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
/**
 * In case of changed node, we need to re-traverse the PHPStan Scope to make all the new nodes aware of what is going on.
 */
final class Changed_Node_Scope_Refresher
{
    /**
     * @readonly
     */
    private Php_Stan_Node_Scope_Resolver $php_stan_node_scope_resolver;
    /**
     * @readonly
     */
    private Scope_Analyzer $scope_analyzer;
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    public function __construct(Php_Stan_Node_Scope_Resolver $php_stan_node_scope_resolver, Scope_Analyzer $scope_analyzer, Simple_Callable_Node_Traverser $simple_callable_node_traverser)
    {
        $this->php_stan_node_scope_resolver = $php_stan_node_scope_resolver;
        $this->scope_analyzer = $scope_analyzer;
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
    }
    public function refresh(Node $node, string $file_path, ?Mutating_Scope $mutating_scope): void
    {
        // nothing to refresh
        if (!$this->scope_analyzer->is_refreshable($node)) {
            return;
        }
        if (!$mutating_scope instanceof Mutating_Scope) {
            $error_message = sprintf('Node "%s" with is missing scope required for scope refresh', get_class($node));
            throw new Should_Not_Happen_Exception($error_message);
        }
        /**
         * The reindex is needed to:
         *      - be used by PHPStan processNodes() that relies on indexed arrays start from 0
         *      - use traverser to avoid issues when multiples rules apply, and higher node remove deep node,
         *        which the next rule use deep node, for example:
         *              - first rule: - Class_ → ClassMethod → remove stmt with index 0
         *              - second rule: - ClassMethod → here fetch the index 0 that no longer exists
         */
        Simple_Callable_Node_Traverser::traverse($node, fn(Node $sub_node): ?Node => \Rector\Application\Node_Attribute_Re_Indexer::re_index_node_attributes($sub_node));
        $stmts = $this->resolve_stmts($node);
        $this->php_stan_node_scope_resolver->process_nodes($stmts, $file_path, $mutating_scope);
    }
    /**
     * @return Stmt[]
     */
    private function resolve_stmts(Node $node): array
    {
        if ($node instanceof Stmt) {
            return [$node];
        }
        if ($node instanceof Expr) {
            return [new Expression($node)];
        }
        // moved from Expr/Stmt to directly under Node on PHPParser 5
        if ($node instanceof Array_Item) {
            return [new Expression(new Array_([$node]))];
        }
        if ($node instanceof Closure_Use) {
            $closure = new Closure();
            $closure->uses[] = $node;
            return [new Expression($closure)];
        }
        if ($node instanceof Declare_Item) {
            return [new Declare_([$node])];
        }
        if ($node instanceof Property_Item) {
            return [new Property(Modifiers::PUBLIC, [$node])];
        }
        if ($node instanceof Static_Var) {
            return [new Static_([$node])];
        }
        if ($node instanceof Use_Item) {
            return [new Use_([$node])];
        }
        if ($node instanceof Param) {
            $closure = new Closure();
            $closure->params[] = $node;
            return [new Expression($closure)];
        }
        if ($node instanceof Attribute_Group) {
            $class = new Class_(null);
            $class->attr_groups[] = $node;
            $this->set_line_attributes_on_class($class, $node);
            return [$class];
        }
        if ($node instanceof Attribute) {
            $class = new Class_(null);
            $class->attr_groups[] = new Attribute_Group([$node]);
            $this->set_line_attributes_on_class($class, $node);
            return [$class];
        }
        if ($node instanceof Arg) {
            $class = new Class_(null, [], ['startLine' => $node->get_start_line(), 'endLine' => $node->get_end_line()]);
            $new = new New_($class, [$node]);
            return [new Expression($new)];
        }
        $error_message = sprintf('Complete parent node of "%s" be a stmt.', get_class($node));
        throw new Should_Not_Happen_Exception($error_message);
    }
    /**
     * @param \PhpParser\Node\Attribute|\PhpParser\Node\AttributeGroup $node
     */
    private function set_line_attributes_on_class(Class_ $class, $node): void
    {
        $this->simple_callable_node_traverser->traverse_nodes_with_callable([$class], function (Node $sub_node) use ($node): Node {
            if ($sub_node->get_start_line() >= 0 && $sub_node->get_end_line() >= 0) {
                return $sub_node;
            }
            $sub_node->set_attribute('startLine', $node->get_start_line());
            $sub_node->set_attribute('endLine', $node->get_end_line());
            return $sub_node;
        });
    }
}