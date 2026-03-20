<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Guard;

use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Inline_Html;
use Php_Parser\Node\Stmt\Namespace_;
use Rector\Php_Parser\Node\Better_Node_Finder;
use Rector\Php_Parser\Node\File_Node;
final class Add_Use_Statement_Guard
{
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @var array<string, bool>
     */
    private array $should_traverse_on_files = [];
    public function __construct(Better_Node_Finder $better_node_finder)
    {
        $this->better_node_finder = $better_node_finder;
    }
    /**
     * @param Stmt[] $stmts
     */
    public function should_traverse(array $stmts, string $file_path): bool
    {
        if (isset($this->should_traverse_on_files[$file_path])) {
            return $this->should_traverse_on_files[$file_path];
        }
        $total_namespaces = 0;
        // just loop the first level stmts to locate namespace to improve performance
        // as namespace is always on first level
        if (isset($stmts[0]) && $stmts[0] instanceof File_Node) {
            $stmts = $stmts[0]->stmts;
        }
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                ++$total_namespaces;
            }
            // skip if 2 namespaces are present
            if ($total_namespaces === 2) {
                return $this->should_traverse_on_files[$file_path] = \false;
            }
        }
        return $this->should_traverse_on_files[$file_path] = !$this->better_node_finder->has_instances_of($stmts, [Inline_Html::class]);
    }
}