<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Finally_;
use Php_Parser\Node\Stmt\Try_Catch;
use Rector\Dead_Code\Node_Analyzer\Expr_Used_In_Node_Analyzer;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Comparing\Node_Comparator;
use Rector\Php_Parser\Node\Better_Node_Finder;
final class Stmts_Manipulator
{
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @readonly
     */
    private Node_Comparator $node_comparator;
    /**
     * @readonly
     */
    private Expr_Used_In_Node_Analyzer $expr_used_in_node_analyzer;
    public function __construct(Simple_Callable_Node_Traverser $simple_callable_node_traverser, Better_Node_Finder $better_node_finder, Node_Comparator $node_comparator, Expr_Used_In_Node_Analyzer $expr_used_in_node_analyzer)
    {
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
        $this->better_node_finder = $better_node_finder;
        $this->node_comparator = $node_comparator;
        $this->expr_used_in_node_analyzer = $expr_used_in_node_analyzer;
    }
    /**
     * @param Stmt[] $stmts
     * @return null|\PhpParser\Node\Expr|\PhpParser\Node\Stmt
     */
    public function get_unwrapped_last_stmt(array $stmts)
    {
        if ($stmts === []) {
            return null;
        }
        $last_stmt_key = array_key_last($stmts);
        $last_stmt = $stmts[$last_stmt_key];
        if ($last_stmt instanceof Expression) {
            $last_stmt->expr->set_attribute(Attribute_Key::COMMENTS, $last_stmt->get_attribute(Attribute_Key::COMMENTS));
            return $last_stmt->expr;
        }
        return $last_stmt;
    }
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function filter_out_existing_stmts(Class_Method $class_method, array $stmts): array
    {
        $this->simple_callable_node_traverser->traverse_nodes_with_callable((array) $class_method->stmts, function (Node $node) use (&$stmts) {
            foreach ($stmts as $key => $assign) {
                if (!$this->node_comparator->are_nodes_equal($node, $assign)) {
                    continue;
                }
                unset($stmts[$key]);
            }
            return null;
        });
        return $stmts;
    }
    /**
     * @param StmtsAware $stmtsAware
     */
    public function is_variable_used_in_next_stmt(Node $stmts_aware, int $jump_to_key, string $variable_name): bool
    {
        if ($stmts_aware->stmts === null) {
            return \false;
        }
        $last_key = array_key_last($stmts_aware->stmts);
        $stmts = [];
        for ($key = $jump_to_key; $key <= $last_key; ++$key) {
            if (!isset($stmts_aware->stmts[$key])) {
                // can be just removed
                continue;
            }
            $stmts[] = $stmts_aware->stmts[$key];
        }
        if ($stmts_aware instanceof Try_Catch) {
            $stmts = array_merge($stmts, $stmts_aware->catches);
            if ($stmts_aware->finally instanceof Finally_) {
                $stmts = array_merge($stmts, $stmts_aware->finally->stmts);
            }
        }
        $variable = new Variable($variable_name);
        return (bool) $this->better_node_finder->find_first($stmts, fn(Node $sub_node): bool => $this->expr_used_in_node_analyzer->is_used($sub_node, $variable));
    }
}