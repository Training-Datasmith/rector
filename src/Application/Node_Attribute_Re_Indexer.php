<?php

declare (strict_types=1);
namespace Rector\Application;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Function_Like;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node\Stmt\Switch_;
use Php_Parser\Node\Stmt\Try_Catch;
use Rector\Php_Parser\Enum\Node_Group;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Node_Attribute_Re_Indexer
{
    public static function re_index_node_attributes(Node $node): ?Node
    {
        self::re_index_stmts_keys($node);
        if ($node instanceof If_) {
            $node->elseifs = array_values($node->elseifs);
            return $node;
        }
        if ($node instanceof Try_Catch) {
            $node->catches = array_values($node->catches);
            return $node;
        }
        if ($node instanceof Function_Like) {
            /** @var ClassMethod|Function_|Closure $node */
            $node->params = array_values($node->params);
            if ($node instanceof Closure) {
                $node->uses = array_values($node->uses);
            }
            return $node;
        }
        if ($node instanceof Call_Like) {
            /** @var FuncCall|MethodCall|New_|NullsafeMethodCall|StaticCall $node */
            $node->args = array_values($node->args);
            return $node;
        }
        if ($node instanceof Switch_) {
            $node->cases = array_values($node->cases);
            return $node;
        }
        return null;
    }
    private static function re_index_stmts_keys(Node $node): ?Node
    {
        if (!Node_Group::is_stmt_aware_node($node) && !$node instanceof Class_Like) {
            return null;
        }
        Assert::property_exists($node, 'stmts');
        if ($node->stmts === null) {
            return null;
        }
        $node->stmts = array_values($node->stmts);
        return $node;
    }
}