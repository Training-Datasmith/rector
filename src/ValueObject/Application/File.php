<?php

declare (strict_types=1);
namespace Rector\Value_Object\Application;

use Php_Parser\Node;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Inline_Html;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node_Finder;
use Php_Parser\Token;
use Rector\Changes_Reporting\Value_Object\Rector_With_Line_Change;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php_Parser\Node\File_Node;
use Rector\Value_Object\Reporting\File_Diff;
final class File
{
    /**
     * @readonly
     */
    private string $file_path;
    private string $file_content;
    private bool $has_changed = \false;
    /**
     * @readonly
     */
    private string $original_file_content;
    private ?File_Diff $file_diff = null;
    /**
     * @var Node[]
     */
    private array $old_stmts = [];
    /**
     * @var Node[]
     */
    private array $new_stmts = [];
    /**
     * @var array<int, Token>
     */
    private array $old_tokens = [];
    /**
     * @var RectorWithLineChange[]
     */
    private array $rector_with_line_changes = [];
    /**
     * Cached result per file
     */
    private ?bool $contains_html = null;
    public function __construct(string $file_path, string $file_content)
    {
        $this->file_path = $file_path;
        $this->file_content = $file_content;
        $this->original_file_content = $file_content;
    }
    public function get_file_path(): string
    {
        return $this->file_path;
    }
    public function get_file_content(): string
    {
        return $this->file_content;
    }
    public function change_file_content(string $new_file_content): void
    {
        if ($this->file_content === $new_file_content) {
            return;
        }
        $this->file_content = $new_file_content;
        $this->has_changed = \true;
    }
    public function get_original_file_content(): string
    {
        return $this->original_file_content;
    }
    public function has_changed(): bool
    {
        return $this->has_changed;
    }
    public function change_has_changed(bool $status): void
    {
        $this->has_changed = $status;
    }
    public function set_file_diff(File_Diff $file_diff): void
    {
        $this->file_diff = $file_diff;
    }
    public function get_file_diff(): ?File_Diff
    {
        return $this->file_diff;
    }
    /**
     * @param Stmt[] $newStmts
     * @param Stmt[] $oldStmts
     * @param array<int, Token> $oldTokens
     */
    public function hydrate_stmts_and_tokens(array $new_stmts, array $old_stmts, array $old_tokens): void
    {
        if ($this->old_stmts !== []) {
            throw new Should_Not_Happen_Exception('Double stmts override');
        }
        $this->old_stmts = $old_stmts;
        $this->new_stmts = $new_stmts;
        $this->old_tokens = $old_tokens;
    }
    /**
     * @return Stmt[]
     */
    public function get_old_stmts(): array
    {
        return $this->old_stmts;
    }
    /**
     * @return Stmt[]
     */
    public function get_new_stmts(): array
    {
        return $this->new_stmts;
    }
    /**
     * @return array<int, Token>
     */
    public function get_old_tokens(): array
    {
        return $this->old_tokens;
    }
    /**
     * @param Node[] $newStmts
     */
    public function change_new_stmts(array $new_stmts): void
    {
        $this->new_stmts = $new_stmts;
    }
    public function add_rector_class_with_line(Rector_With_Line_Change $rector_with_line_change): void
    {
        $this->rector_with_line_changes[] = $rector_with_line_change;
    }
    /**
     * This node returns top most node,
     * that includes use imports
     * @return \PhpParser\Node\Stmt\Namespace_|\Rector\PhpParser\Node\FileNode|null
     */
    public function get_use_imports_root_node()
    {
        if ($this->new_stmts === []) {
            return null;
        }
        $first_stmt = $this->new_stmts[0];
        if ($first_stmt instanceof File_Node) {
            if (!$first_stmt->is_namespaced()) {
                return $first_stmt;
            }
            // return sole Namespace, or none
            $namespaces = [];
            foreach ($first_stmt->stmts as $stmt) {
                if ($stmt instanceof Namespace_) {
                    $namespaces[] = $stmt;
                }
            }
            if (count($namespaces) === 1) {
                return $namespaces[0];
            }
        }
        return null;
    }
    /**
     * @return RectorWithLineChange[]
     */
    public function get_rector_with_line_changes(): array
    {
        return $this->rector_with_line_changes;
    }
    public function contains_html(): bool
    {
        if ($this->contains_html !== null) {
            return $this->contains_html;
        }
        $node_finder = new Node_Finder();
        $this->contains_html = (bool) $node_finder->find_first_instance_of($this->old_stmts, Inline_Html::class);
        return $this->contains_html;
    }
    public function get_file_node(): ?File_Node
    {
        if ($this->new_stmts === []) {
            return null;
        }
        if ($this->new_stmts[0] instanceof File_Node) {
            return $this->new_stmts[0];
        }
        return null;
    }
    public function has_shebang(): bool
    {
        return strncmp($this->file_content, '#!', strlen('#!')) === 0;
    }
}