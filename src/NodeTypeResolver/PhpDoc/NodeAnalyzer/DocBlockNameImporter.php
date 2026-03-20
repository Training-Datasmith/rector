<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Doc\Node_Analyzer;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Rector\Node_Type_Resolver\Php_Doc_Node_Visitor\Name_Importing_Php_Doc_Node_Visitor;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
final class Doc_Block_Name_Importer
{
    /**
     * @readonly
     */
    private Name_Importing_Php_Doc_Node_Visitor $name_importing_php_doc_node_visitor;
    public function __construct(Name_Importing_Php_Doc_Node_Visitor $name_importing_php_doc_node_visitor)
    {
        $this->name_importing_php_doc_node_visitor = $name_importing_php_doc_node_visitor;
    }
    public function import_names(Php_Doc_Node $php_doc_node, Node $node): bool
    {
        if ($php_doc_node->children === []) {
            return \false;
        }
        $this->name_importing_php_doc_node_visitor->set_current_node($node);
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $php_doc_node_traverser->add_php_doc_node_visitor($this->name_importing_php_doc_node_visitor);
        $php_doc_node_traverser->traverse($php_doc_node);
        return $this->name_importing_php_doc_node_visitor->has_changed();
    }
}