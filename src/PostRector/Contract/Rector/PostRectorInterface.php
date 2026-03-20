<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Contract\Rector;

use Php_Parser\Node\Stmt;
use Php_Parser\Node_Visitor;
use Rector\Value_Object\Application\File;
/**
 * @internal
 */
interface Post_Rector_Interface extends Node_Visitor
{
    /**
     * @param Stmt[] $stmts
     */
    public function should_traverse(array $stmts): bool;
    public function set_file(File $file): void;
}