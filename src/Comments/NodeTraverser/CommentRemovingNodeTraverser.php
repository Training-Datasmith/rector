<?php

declare (strict_types=1);
namespace Rector\Comments\Node_Traverser;

use Php_Parser\Node_Traverser;
use Rector\Comments\Node_Visitor\Comment_Removing_Node_Visitor;
final class Comment_Removing_Node_Traverser extends Node_Traverser
{
    public function __construct(Comment_Removing_Node_Visitor $comment_removing_node_visitor)
    {
        parent::__construct($comment_removing_node_visitor);
    }
}