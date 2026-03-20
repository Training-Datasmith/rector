<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
/**
 * @api
 *
 * Mimics https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeVisitor/ParentConnectingVisitor.php
 *
 * @see \Rector\Tests\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\ParentConnectingPhpDocNodeVisitorTest
 */
final class Parent_Connecting_Php_Doc_Node_Visitor extends \Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor
{
    /**
     * @var Node[]
     */
    private array $stack = [];
    public function before_traverse(Node $node): void
    {
        $this->stack = [$node];
    }
    public function enter_node(Node $node): Node
    {
        if ($this->stack !== []) {
            $parent_node = $this->stack[count($this->stack) - 1];
            $node->set_attribute(Php_Doc_Attribute_Key::PARENT, $parent_node);
        }
        $this->stack[] = $node;
        return $node;
    }
    /**
     * @return null|int|\PhpParser\Node|Node[] Replacement node (or special return
     */
    public function leave_node(Node $node)
    {
        array_pop($this->stack);
        return null;
    }
}