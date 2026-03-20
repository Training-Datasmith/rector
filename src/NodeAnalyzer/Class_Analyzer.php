<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Stmt\Class_;
final class Class_Analyzer
{
    public function is_anonymous_class(Node $node): bool
    {
        if ($node instanceof New_) {
            return $this->is_anonymous_class($node->class);
        }
        if ($node instanceof Class_) {
            return $node->is_anonymous();
        }
        return \false;
    }
}