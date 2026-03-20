<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Doc\Node_Analyzer;

use Php_Parser\Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Node_Type_Resolver\Php_Doc_Node_Visitor\Class_Rename_Php_Doc_Node_Visitor;
use Rector\Node_Type_Resolver\Value_Object\Old_To_New_Type;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
final class Doc_Block_Class_Renamer
{
    /**
     * @readonly
     */
    private Class_Rename_Php_Doc_Node_Visitor $class_rename_php_doc_node_visitor;
    public function __construct(Class_Rename_Php_Doc_Node_Visitor $class_rename_php_doc_node_visitor)
    {
        $this->class_rename_php_doc_node_visitor = $class_rename_php_doc_node_visitor;
    }
    /**
     * @param OldToNewType[] $oldToNewTypes
     */
    public function rename_php_doc_type(Php_Doc_Info $php_doc_info, array $old_to_new_types, Node $current_php_node): bool
    {
        if ($old_to_new_types === []) {
            return \false;
        }
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $php_doc_node_traverser->add_php_doc_node_visitor($this->class_rename_php_doc_node_visitor);
        $this->class_rename_php_doc_node_visitor->set_current_php_node($current_php_node);
        $this->class_rename_php_doc_node_visitor->set_old_to_new_types($old_to_new_types);
        $php_doc_node_traverser->traverse($php_doc_info->get_php_doc_node());
        return $this->class_rename_php_doc_node_visitor->has_changed();
    }
}