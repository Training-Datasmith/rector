<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Rector;

use Override;
use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Group_Use;
use Php_Parser\Node\Stmt\Use_;
use Rector\Coding_Style\Node\Name_Importer;
use Rector\Naming\Naming\Use_Imports_Resolver;
use Rector\Post_Rector\Guard\Add_Use_Statement_Guard;
final class Name_Importing_Post_Rector extends \Rector\Post_Rector\Rector\Abstract_Post_Rector
{
    /**
     * @readonly
     */
    private Name_Importer $name_importer;
    /**
     * @readonly
     */
    private Use_Imports_Resolver $use_imports_resolver;
    /**
     * @readonly
     */
    private Add_Use_Statement_Guard $add_use_statement_guard;
    /**
     * @var array<Use_|GroupUse>
     */
    private array $current_uses = [];
    public function __construct(Name_Importer $name_importer, Use_Imports_Resolver $use_imports_resolver, Add_Use_Statement_Guard $add_use_statement_guard)
    {
        $this->name_importer = $name_importer;
        $this->use_imports_resolver = $use_imports_resolver;
        $this->add_use_statement_guard = $add_use_statement_guard;
    }
    /**
     * @return Stmt[]
     */
    public function before_traverse(array $nodes): array
    {
        $this->current_uses = $this->use_imports_resolver->resolve();
        return $nodes;
    }
    public function enter_node(Node $node): ?\Php_Parser\Node\Name
    {
        if (!$node instanceof Fully_Qualified) {
            return null;
        }
        $name = $this->name_importer->import_name($node, $this->get_file(), $this->current_uses);
        if (!$name instanceof Name) {
            return null;
        }
        $this->add_rector_class_with_line($node);
        return $name;
    }
    /**
     * @param Stmt[] $stmts
     */
    #[Override]
    public function should_traverse(array $stmts): bool
    {
        return $this->add_use_statement_guard->should_traverse($stmts, $this->get_file()->get_file_path());
    }
}