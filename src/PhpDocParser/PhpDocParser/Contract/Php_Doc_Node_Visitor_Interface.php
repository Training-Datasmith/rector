<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Php_Doc_Parser\Contract;

use Php_Stan\Php_Doc_Parser\Ast\Node;
/**
 * Inspired by https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeVisitor.php
 */
interface Php_Doc_Node_Visitor_Interface
{
    public function before_traverse(Node $node): void;
    /**
     * @return int|\PHPStan\PhpDocParser\Ast\Node|null
     */
    public function enter_node(Node $node);
    /**
     * @return null|int|\PhpParser\Node|Node[] Replacement node (or special return)
     */
    public function leave_node(Node $node);
    public function after_traverse(Node $node): void;
}