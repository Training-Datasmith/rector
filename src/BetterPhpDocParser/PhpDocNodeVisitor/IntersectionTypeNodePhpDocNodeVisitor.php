<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Intersection_Type_Node;
use Rector\Better_Php_Doc_Parser\Attributes\Attribute_Mirrorer;
use Rector\Better_Php_Doc_Parser\Contract\Base_Php_Doc_Node_Visitor_Interface;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Intersection_Type_Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor;
final class Intersection_Type_Node_Php_Doc_Node_Visitor extends Abstract_Php_Doc_Node_Visitor implements Base_Php_Doc_Node_Visitor_Interface
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
        if (!$node instanceof Intersection_Type_Node) {
            return null;
        }
        if ($node instanceof Brackets_Aware_Intersection_Type_Node) {
            return null;
        }
        $brackets_aware_intersection_type_node = new Brackets_Aware_Intersection_Type_Node($node->types);
        $this->attribute_mirrorer->mirror($node, $brackets_aware_intersection_type_node);
        return $brackets_aware_intersection_type_node;
    }
}