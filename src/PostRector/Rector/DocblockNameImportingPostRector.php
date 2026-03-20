<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Rector;

use Override;
use Php_Parser\Node;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
use Rector\Comments\Node_Doc_Block\Doc_Block_Updater;
use Rector\Node_Type_Resolver\Php_Doc\Node_Analyzer\Doc_Block_Name_Importer;
use Rector\Post_Rector\Guard\Add_Use_Statement_Guard;
final class Docblock_Name_Importing_Post_Rector extends \Rector\Post_Rector\Rector\Abstract_Post_Rector
{
    /**
     * @readonly
     */
    private Doc_Block_Name_Importer $doc_block_name_importer;
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    /**
     * @readonly
     */
    private Doc_Block_Updater $doc_block_updater;
    /**
     * @readonly
     */
    private Add_Use_Statement_Guard $add_use_statement_guard;
    public function __construct(Doc_Block_Name_Importer $doc_block_name_importer, Php_Doc_Info_Factory $php_doc_info_factory, Doc_Block_Updater $doc_block_updater, Add_Use_Statement_Guard $add_use_statement_guard)
    {
        $this->doc_block_name_importer = $doc_block_name_importer;
        $this->php_doc_info_factory = $php_doc_info_factory;
        $this->doc_block_updater = $doc_block_updater;
        $this->add_use_statement_guard = $add_use_statement_guard;
    }
    public function enter_node(Node $node): ?\Php_Parser\Node
    {
        if (!$node instanceof Stmt && !$node instanceof Param) {
            return null;
        }
        $php_doc_info = $this->php_doc_info_factory->create_from_node($node);
        if (!$php_doc_info instanceof Php_Doc_Info) {
            return null;
        }
        $has_doc_changed = $this->doc_block_name_importer->import_names($php_doc_info->get_php_doc_node(), $node);
        if (!$has_doc_changed) {
            return null;
        }
        $this->add_rector_class_with_line($node);
        $this->doc_block_updater->update_refactored_node_with_php_doc_info($node);
        return $node;
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