<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Rector;

use Override;
use Php_Parser\Node;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node_Visitor;
use Rector\Coding_Style\Application\Use_Imports_Remover;
use Rector\Configuration\Renamed_Classes_Data_Collector;
use Rector\Php_Parser\Node\File_Node;
use Rector\Post_Rector\Guard\Add_Use_Statement_Guard;
use Rector\Renaming\Collector\Renamed_Name_Collector;
final class Class_Renaming_Post_Rector extends \Rector\Post_Rector\Rector\Abstract_Post_Rector
{
    /**
     * @readonly
     */
    private Renamed_Classes_Data_Collector $renamed_classes_data_collector;
    /**
     * @readonly
     */
    private Use_Imports_Remover $use_imports_remover;
    /**
     * @readonly
     */
    private Renamed_Name_Collector $renamed_name_collector;
    /**
     * @readonly
     */
    private Add_Use_Statement_Guard $add_use_statement_guard;
    /**
     * @var array<string, string>
     */
    private array $old_to_new_classes = [];
    public function __construct(Renamed_Classes_Data_Collector $renamed_classes_data_collector, Use_Imports_Remover $use_imports_remover, Renamed_Name_Collector $renamed_name_collector, Add_Use_Statement_Guard $add_use_statement_guard)
    {
        $this->renamed_classes_data_collector = $renamed_classes_data_collector;
        $this->use_imports_remover = $use_imports_remover;
        $this->renamed_name_collector = $renamed_name_collector;
        $this->add_use_statement_guard = $add_use_statement_guard;
    }
    /**
     * @return \PhpParser\Node\Stmt\Namespace_|\Rector\PhpParser\Node\FileNode|int|null
     */
    public function enter_node(Node $node)
    {
        if ($node instanceof File_Node) {
            // handle in Namespace_ node
            if ($node->is_namespaced()) {
                return null;
            }
            // handle here
            $removed_uses = $this->renamed_classes_data_collector->get_old_classes();
            if ($this->use_imports_remover->remove_imports_from_stmts($node, $removed_uses)) {
                $this->add_rector_class_with_line($node);
            }
            $this->renamed_name_collector->reset();
            return $node;
        }
        if ($node instanceof Namespace_) {
            $removed_uses = $this->renamed_classes_data_collector->get_old_classes();
            if ($this->use_imports_remover->remove_imports_from_stmts($node, $removed_uses)) {
                $this->add_rector_class_with_line($node);
            }
            $this->renamed_name_collector->reset();
            return $node;
        }
        // nothing else to handle here, as first 2 nodes we'll hit are handled above
        return Node_Visitor::STOP_TRAVERSAL;
    }
    #[Override]
    public function should_traverse(array $stmts): bool
    {
        $this->old_to_new_classes = $this->renamed_classes_data_collector->get_old_to_new_classes();
        if ($this->old_to_new_classes === []) {
            return \false;
        }
        return $this->add_use_statement_guard->should_traverse($stmts, $this->get_file()->get_file_path());
    }
}