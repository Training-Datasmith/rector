<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc;

use Override;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
/**
 * Useful for annotation class based annotation, e.g. @ORM\Entity to prevent space
 * between the @ORM\Entity and (someContent)
 */
final class Spaceless_Php_Doc_Tag_Node extends Php_Doc_Tag_Node
{
    #[Override]
    public function __toString(): string
    {
        return $this->name . $this->value;
    }
}