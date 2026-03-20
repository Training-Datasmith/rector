<?php

declare (strict_types=1);
namespace Rector\Comments;

use Php_Parser\Node;
use Rector\Comments\Node_Traverser\Comment_Removing_Node_Traverser;
/**
 * @see \Rector\Tests\Comments\CommentRemover\CommentRemoverTest
 */
final class Comment_Remover
{
    /**
     * @readonly
     */
    private Comment_Removing_Node_Traverser $comment_removing_node_traverser;
    public function __construct(Comment_Removing_Node_Traverser $comment_removing_node_traverser)
    {
        $this->comment_removing_node_traverser = $comment_removing_node_traverser;
    }
    /**
     * @param Node[]|Node|null $node
     * @return Node[]|null
     */
    public function remove_from_node($node): ?array
    {
        if ($node === null) {
            return null;
        }
        $nodes = is_array($node) ? $node : [$node];
        return $this->comment_removing_node_traverser->traverse($nodes);
    }
}