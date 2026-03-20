<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Group_Use;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node\Stmt\Use_;
/**
 * Inspired by https://github.com/phpstan/phpstan-src/commit/ed81c3ad0b9877e6122c79b4afda9d10f3994092
 */
class File_Node extends Stmt
{
    /**
     * @var Stmt[]
     */
    public array $stmts;
    /**
     * @param Stmt[] $stmts
     */
    public function __construct(array $stmts)
    {
        $this->stmts = $stmts;
        $first_stmt = $stmts[0] ?? null;
        $attributes = $first_stmt instanceof Node ? $first_stmt->get_attributes() : [];
        parent::__construct($attributes);
    }
    /**
     * This triggers Printed method with "pFileNode" name
     * @see \Rector\PhpParser\Printer\BetterStandardPrinter::pStmt_FileNode()
     */
    public function get_type(): string
    {
        return 'Stmt_FileNode';
    }
    /**
     * @return array<int, string>
     */
    public function get_sub_node_names(): array
    {
        return ['stmts'];
    }
    public function is_namespaced(): bool
    {
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                return \true;
            }
        }
        return \false;
    }
    public function get_namespace(): ?Namespace_
    {
        /** @var Namespace_[] $namespaces */
        $namespaces = array_filter($this->stmts, static fn(Stmt $stmt): bool => $stmt instanceof Namespace_);
        if (count($namespaces) === 1) {
            return current($namespaces);
        }
        return null;
    }
    /**
     * @return array<Use_|GroupUse>
     */
    public function get_uses_and_group_uses(): array
    {
        $root_node = $this->get_namespace();
        if (!$root_node instanceof Namespace_) {
            $root_node = $this;
        }
        return array_filter($root_node->stmts, static fn(Stmt $stmt): bool => $stmt instanceof Use_ || $stmt instanceof Group_Use);
    }
    /**
     * @return Use_[]
     */
    public function get_uses(): array
    {
        $root_node = $this->get_namespace();
        if (!$root_node instanceof Namespace_) {
            $root_node = $this;
        }
        return array_filter($root_node->stmts, static fn(Stmt $stmt): bool => $stmt instanceof Use_);
    }
}