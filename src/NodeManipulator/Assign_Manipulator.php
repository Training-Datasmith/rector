<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Error_Suppress;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\List_;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Function_Like;
use Rector\Node_Analyzer\Property_Fetch_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Nesting_Scope\Context_Analyzer;
use Rector\Php72\Value_Object\List_And_Each;
use Rector\Php_Parser\Node\Better_Node_Finder;
final class Assign_Manipulator
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
    private Property_Fetch_Analyzer $property_fetch_analyzer;
    /**
     * @readonly
     */
    private Context_Analyzer $context_analyzer;
    public function __construct(Node_Name_Resolver $node_name_resolver, Better_Node_Finder $better_node_finder, Property_Fetch_Analyzer $property_fetch_analyzer, Context_Analyzer $context_analyzer)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->better_node_finder = $better_node_finder;
        $this->property_fetch_analyzer = $property_fetch_analyzer;
        $this->context_analyzer = $context_analyzer;
    }
    /**
     * Matches:
     * list([1, 2]) = each($items)
     */
    public function match_list_and_each(Assign $assign): ?List_And_Each
    {
        // could be behind error suppress
        if ($assign->expr instanceof Error_Suppress) {
            $error_suppress = $assign->expr;
            $bare_expr = $error_suppress->expr;
        } else {
            $bare_expr = $assign->expr;
        }
        if (!$bare_expr instanceof Func_Call) {
            return null;
        }
        if (!$assign->var instanceof List_) {
            return null;
        }
        if (!$this->node_name_resolver->is_name($bare_expr, 'each')) {
            return null;
        }
        // no placeholders
        if ($bare_expr->is_first_class_callable()) {
            return null;
        }
        return new List_And_Each($assign->var, $bare_expr);
    }
    /**
     * @api doctrine
     * @return array<PropertyFetch|StaticPropertyFetch>
     */
    public function resolve_assigns_to_local_property_fetches(Function_Like $function_like): array
    {
        return $this->better_node_finder->find((array) $function_like->get_stmts(), function (Node $node): bool {
            if (!$this->property_fetch_analyzer->is_local_property_fetch($node)) {
                return \false;
            }
            return $this->context_analyzer->is_left_part_of_assign($node);
        });
    }
}