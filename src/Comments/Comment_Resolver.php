<?php

declare (strict_types=1);
namespace Rector\Comments;

use Php_Parser\Comment;
use Php_Parser\Node\Stmt;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Comment_Resolver
{
    /**
     * @param int|float $rangeLine
     * @return float|int
     */
    public function resolve_range_line_from_comment($range_line, int $end_line, Stmt $next_stmt)
    {
        /** @var Comment[]|null $comments */
        $comments = $next_stmt->get_attribute(Attribute_Key::COMMENTS);
        if ($this->has_no_comment($comments)) {
            return $range_line;
        }
        /** @var Comment[] $comments */
        $first_comment = $comments[0];
        $line = $first_comment->get_start_line();
        return $line - $end_line;
    }
    /**
     * @param Comment[]|null $comments
     */
    private function has_no_comment(?array $comments): bool
    {
        return $comments === null || $comments === [];
    }
}