<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Parser;

use Php_Parser\Node as PhpNode;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Fetch_Node;
use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Rector\Better_Php_Doc_Parser\Contract\Php_Doc_Parser\Php_Doc_Node_Decorator_Interface;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
use Rector\Static_Type_Mapper\Naming\Name_Scope_Factory;
/**
 * Decorate node with fully qualified class name for const epxr,
 * e.g. Direction::*
 */
final class Const_Expr_Class_Name_Decorator implements Php_Doc_Node_Decorator_Interface
{
    /**
     * @readonly
     */
    private Name_Scope_Factory $name_scope_factory;
    /**
     * @readonly
     */
    private Php_Doc_Node_Traverser $php_doc_node_traverser;
    public function __construct(Name_Scope_Factory $name_scope_factory, Php_Doc_Node_Traverser $php_doc_node_traverser)
    {
        $this->name_scope_factory = $name_scope_factory;
        $this->php_doc_node_traverser = $php_doc_node_traverser;
    }
    public function decorate(Php_Doc_Node $php_doc_node, Php_Node $php_node): void
    {
        // iterating all phpdocs has big overhead. peek into the phpdoc to exit early
        if (strpos($php_doc_node->__toString(), '::') === \false) {
            return;
        }
        $this->php_doc_node_traverser->traverse_with_callable($php_doc_node, '', function (Node $node) use ($php_node): ?\Php_Stan\Php_Doc_Parser\Ast\Node {
            if (!$node instanceof Const_Fetch_Node) {
                return null;
            }
            $class_name = $this->resolve_fully_qualified_class($node, $php_node);
            $node->set_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS, $class_name);
            return $node;
        });
    }
    private function resolve_fully_qualified_class(Const_Fetch_Node $const_fetch_node, Php_Node $php_node): string
    {
        $name_scope = $this->name_scope_factory->create_name_scope_from_node_without_template_types($php_node);
        return $name_scope->resolve_string_name($const_fetch_node->class_name);
    }
}