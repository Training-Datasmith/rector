<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Contract\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
interface Php_Doc_Node_Decorator_Interface
{
    public function decorate(Php_Doc_Node $php_doc_node, Node $php_node): void;
}