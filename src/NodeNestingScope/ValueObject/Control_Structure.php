<?php

declare (strict_types=1);
namespace Rector\Node_Nesting_Scope\Value_Object;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Match_;
use Php_Parser\Node\Stmt\Case_;
use Php_Parser\Node\Stmt\Catch_;
use Php_Parser\Node\Stmt\Do_;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\Else_If_;
use Php_Parser\Node\Stmt\Foreach_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node\Stmt\Switch_;
use Php_Parser\Node\Stmt\While_;
final class Control_Structure
{
    /**
     * These situations happens only if condition is met
     * @var array<class-string<Node>>
     */
    public const CONDITIONAL_NODE_SCOPE_TYPES = [If_::class, While_::class, Do_::class, Else_::class, Else_If_::class, Catch_::class, Case_::class, Match_::class, Switch_::class, Foreach_::class];
}