<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Callable_Type_Node;
use Rector\Better_Php_Doc_Parser\Attributes\Attribute_Mirrorer;
use Rector\Better_Php_Doc_Parser\Contract\Base_Php_Doc_Node_Visitor_Interface;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Spacing_Aware_Callable_Type_Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor;
final class Callable_Type_Php_Doc_Node_Visitor extends Abstract_Php_Doc_Node_Visitor implements Base_Php_Doc_Node_Visitor_Interface
{
    /**
     * @readonly
     */
    private Attribute_Mirrorer $attribute_mirrorer;
    public function __construct(Attribute_Mirrorer $attribute_mirrorer)
    {
        $this->attribute_mirrorer = $attribute_mirrorer;
    }
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Callable_Type_Node) {
            return null;
        }
        if ($node instanceof Spacing_Aware_Callable_Type_Node) {
            return null;
        }
        $spacing_aware_callable_type_node = new Spacing_Aware_Callable_Type_Node($node->identifier, $node->parameters, $node->return_type, []);
        $this->attribute_mirrorer->mirror($node, $spacing_aware_callable_type_node);
        return $spacing_aware_callable_type_node;
    }
}