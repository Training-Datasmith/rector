<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Type;

use Override;
use Php_Stan\Php_Doc_Parser\Ast\Type\Intersection_Type_Node;
final class Brackets_Aware_Intersection_Type_Node extends Intersection_Type_Node
{
    #[Override]
    public function __toString(): string
    {
        return implode('&', $this->types);
    }
}