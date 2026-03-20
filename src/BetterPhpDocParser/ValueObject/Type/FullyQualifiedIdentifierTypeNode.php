<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Type;

use Override;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
final class Fully_Qualified_Identifier_Type_Node extends Identifier_Type_Node
{
    #[Override]
    public function __toString(): string
    {
        return '\\' . ltrim($this->name, '\\');
    }
}