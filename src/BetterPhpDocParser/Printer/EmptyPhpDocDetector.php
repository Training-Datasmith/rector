<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Printer;

use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Text_Node;
final class Empty_Php_Doc_Detector
{
    public function is_php_doc_node_empty(Php_Doc_Node $php_doc_node): bool
    {
        if ($php_doc_node->children === []) {
            return \true;
        }
        foreach ($php_doc_node->children as $php_doc_child_node) {
            if ($php_doc_child_node instanceof Php_Doc_Text_Node) {
                if ($php_doc_child_node->text !== '') {
                    return \false;
                }
            } else {
                return \false;
            }
        }
        return \true;
    }
}