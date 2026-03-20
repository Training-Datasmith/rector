<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Exit_;
use Php_Parser\Node\Expr\Throw_;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Break_;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Continue_;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\Expression;
use Php_Parser\Node\Stmt\Finally_;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node\Stmt\Goto_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node\Stmt\Inline_Html;
use Php_Parser\Node\Stmt\Label;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node\Stmt\Nop;
use Php_Parser\Node\Stmt\Return_;
use Php_Parser\Node\Stmt\Switch_;
use Php_Parser\Node\Stmt\Try_Catch;
use Rector\Php_Parser\Node\File_Node;
final class Terminated_Node_Analyzer
{
    /**
     * @var array<class-string<Node>>
     */
    private const TERMINABLE_NODES = [Return_::class, Break_::class, Continue_::class];
    /**
     * @var array<class-string<Node>>
     */
    private const TERMINABLE_NODES_BY_ITS_STMTS = [Try_Catch::class, If_::class, Switch_::class];
    /**
     * @var array<class-string<Node>>
     */
    private const ALLOWED_CONTINUE_CURRENT_STMTS = [Inline_Html::class, Nop::class];
    /**
     * @param StmtsAware $stmtsAware
     */
    public function is_always_terminated(Node $stmts_aware, Stmt $node, Stmt $current_stmt): bool
    {
        if (in_array(get_class($current_stmt), self::ALLOWED_CONTINUE_CURRENT_STMTS, \true)) {
            return \false;
        }
        if (($stmts_aware instanceof File_Node || $stmts_aware instanceof Namespace_) && ($current_stmt instanceof Class_Like || $current_stmt instanceof Function_)) {
            return \false;
        }
        if (!in_array(get_class($node), self::TERMINABLE_NODES_BY_ITS_STMTS, \true)) {
            return $this->is_terminated_node($node, $current_stmt);
        }
        if ($node instanceof Try_Catch) {
            return $this->is_terminated_in_last_stmts_try_catch($node, $current_stmt);
        }
        if ($node instanceof If_) {
            return $this->is_terminated_in_last_stmts_if($node, $current_stmt);
        }
        /** @var Switch_ $node */
        return $this->is_terminated_in_last_stmts_switch($node, $current_stmt);
    }
    private function is_terminated_node(Node $previous_node, Node $current_stmt): bool
    {
        if (in_array(get_class($previous_node), self::TERMINABLE_NODES, \true)) {
            return \true;
        }
        if ($previous_node instanceof Expression && ($previous_node->expr instanceof Exit_ || $previous_node->expr instanceof Throw_)) {
            return \true;
        }
        if ($previous_node instanceof Goto_) {
            return !$current_stmt instanceof Label;
        }
        return \false;
    }
    private function is_terminated_in_last_stmts_switch(Switch_ $switch, Stmt $stmt): bool
    {
        if ($switch->cases === []) {
            return \false;
        }
        $has_default = \false;
        foreach ($switch->cases as $key => $case) {
            if (!$case->cond instanceof Expr) {
                $has_default = \true;
            }
            if ($case->stmts === [] && isset($switch->cases[$key + 1])) {
                continue;
            }
            if (!$this->is_terminated_in_last_stmts($case->stmts, $stmt)) {
                return \false;
            }
        }
        return $has_default;
    }
    private function is_terminated_in_last_stmts_try_catch(Try_Catch $try_catch, Stmt $stmt): bool
    {
        if ($try_catch->finally instanceof Finally_ && $this->is_terminated_in_last_stmts($try_catch->finally->stmts, $stmt)) {
            return \true;
        }
        foreach ($try_catch->catches as $catch) {
            if (!$this->is_terminated_in_last_stmts($catch->stmts, $stmt)) {
                return \false;
            }
        }
        return $this->is_terminated_in_last_stmts($try_catch->stmts, $stmt);
    }
    private function is_terminated_in_last_stmts_if(If_ $if, Stmt $stmt): bool
    {
        // Without ElseIf_[] and Else_, after If_ is possibly executable
        if ($if->elseifs === [] && !$if->else instanceof Else_) {
            return \false;
        }
        foreach ($if->elseifs as $elseif) {
            if (!$this->is_terminated_in_last_stmts($elseif->stmts, $stmt)) {
                return \false;
            }
        }
        if (!$this->is_terminated_in_last_stmts($if->stmts, $stmt)) {
            return \false;
        }
        if (!$if->else instanceof Else_) {
            return \false;
        }
        return $this->is_terminated_in_last_stmts($if->else->stmts, $stmt);
    }
    /**
     * @param Stmt[] $stmts
     */
    private function is_terminated_in_last_stmts(array $stmts, Node $node): bool
    {
        if ($stmts === []) {
            return \false;
        }
        $last_key = array_key_last($stmts);
        $last_node = $stmts[$last_key];
        if (isset($stmts[$last_key - 1]) && !$this->is_terminated_node($stmts[$last_key - 1], $node)) {
            return \false;
        }
        if ($last_node instanceof Expression) {
            return $last_node->expr instanceof Exit_ || $last_node->expr instanceof Throw_;
        }
        return $last_node instanceof Return_;
    }
}