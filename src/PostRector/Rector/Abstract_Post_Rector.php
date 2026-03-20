<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Rector;

use Php_Parser\Node;
use Php_Parser\Node\Stmt;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Changes_Reporting\Value_Object\Rector_With_Line_Change;
use Rector\Post_Rector\Contract\Rector\Post_Rector_Interface;
use Rector\Value_Object\Application\File;
use Rector_Prefix202603\Webmozart\Assert\Assert;
abstract class Abstract_Post_Rector extends Node_Visitor_Abstract implements Post_Rector_Interface
{
    private ?\Rector\Value_Object\Application\File $file = null;
    /**
     * @param Stmt[] $stmts
     */
    public function should_traverse(array $stmts): bool
    {
        return \true;
    }
    public function set_file(File $file): void
    {
        $this->file = $file;
    }
    public function get_file(): File
    {
        Assert::is_instance_of($this->file, File::class);
        return $this->file;
    }
    protected function add_rector_class_with_line(Node $node): void
    {
        Assert::is_instance_of($this->file, File::class);
        $rector_with_line_change = new Rector_With_Line_Change(static::class, $node->get_start_line());
        $this->file->add_rector_class_with_line($rector_with_line_change);
    }
}