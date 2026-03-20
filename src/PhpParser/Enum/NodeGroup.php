<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Enum;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Stmt\Block;
use Php_Parser\Node\Stmt\Case_;
use Php_Parser\Node\Stmt\Catch_;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Declare_;
use Php_Parser\Node\Stmt\Do_;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\Else_If_;
use Php_Parser\Node\Stmt\Finally_;
use Php_Parser\Node\Stmt\For_;
use Php_Parser\Node\Stmt\Foreach_;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Switch_;
use Php_Parser\Node\Stmt\Trait_;
use Php_Parser\Node\Stmt\Try_Catch;
use Php_Parser\Node\Stmt\While_;
use Rector\Php_Parser\Node\File_Node;
final class Node_Group
{
    /**
     * These nodes have Stmt[] $stmts iterable public property
     *
     * If https://github.com/nikic/PHP-Parser/pull/1113 gets merged, can replace those.
     *
     * @var array<class-string<Node>>
     */
    public const STMTS_AWARE = [Block::class, Closure::class, Case_::class, Catch_::class, Class_Method::class, Do_::class, Else_::class, Else_If_::class, Finally_::class, For_::class, Foreach_::class, Function_::class, If_::class, Namespace_::class, Try_Catch::class, While_::class, File_Node::class, Declare_::class];
    /**
     * @var array<class-string<Node>>
     */
    public const STMTS_TO_HAVE_NEXT_NEWLINE = [Class_Method::class, Function_::class, Property::class, If_::class, Foreach_::class, Do_::class, While_::class, For_::class, Class_Const::class, Try_Catch::class, Class_::class, Trait_::class, Interface_::class, Switch_::class];
    public static function is_stmt_aware_node(Node $node): bool
    {
        foreach (self::STMTS_AWARE as $stmt_aware_class) {
            if ($node instanceof $stmt_aware_class) {
                return \true;
            }
        }
        return \false;
    }
}