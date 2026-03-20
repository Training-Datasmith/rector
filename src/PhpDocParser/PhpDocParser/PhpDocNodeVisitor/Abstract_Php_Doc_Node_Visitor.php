<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Contract\Php_Doc_Node_Visitor_Interface;
/**
 * Inspired by https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeVisitorAbstract.php
 */
abstract class Abstract_Php_Doc_Node_Visitor implements Php_Doc_Node_Visitor_Interface
{
    public function before_traverse(Node $node): void
    {
    }
    /**
     * @return int|\PHPStan\PhpDocParser\Ast\Node|null
     */
    public function enter_node(Node $node)
    {
        return null;
    }
    /**
     * @return null|int|\PhpParser\Node|Node[] Replacement node (or special return)
     */
    public function leave_node(Node $node)
    {
        return null;
    }
    public function after_traverse(Node $node): void
    {
    }
}