<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Ternary;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Dead_Code\Condition_Resolver;
use Rector\Dead_Code\Value_Object\Version_Compare_Condition;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Node_Traverser\Simple_Node_Traverser;
final class Php_Version_Condition_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    /**
     * @readonly
     */
    private Condition_Resolver $condition_resolver;
    public function __construct(Condition_Resolver $condition_resolver)
    {
        $this->condition_resolver = $condition_resolver;
    }
    public function enter_node(Node $node): ?Node
    {
        if (($node instanceof Ternary || $node instanceof If_) && $this->has_version_compare_cond($node)) {
            if ($node instanceof Ternary) {
                $nodes = [$node->else];
                if ($node->if instanceof Node) {
                    $nodes[] = $node->if;
                }
            } else {
                $nodes = $node->stmts;
            }
            Simple_Node_Traverser::decorate_with_attribute_value($nodes, Attribute_Key::PHP_VERSION_CONDITIONED, \true);
        }
        return null;
    }
    /**
     * @param \PhpParser\Node\Stmt\If_|\PhpParser\Node\Expr\Ternary $ifOrTernary
     */
    private function has_version_compare_cond($if_or_ternary): bool
    {
        if (!$if_or_ternary->cond instanceof Func_Call) {
            return \false;
        }
        $version_compare = $this->condition_resolver->resolve_from_expr($if_or_ternary->cond);
        return $version_compare instanceof Version_Compare_Condition;
    }
}