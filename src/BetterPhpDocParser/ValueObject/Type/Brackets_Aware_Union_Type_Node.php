<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Type;

use Override;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Union_Type_Node;
final class Brackets_Aware_Union_Type_Node extends Union_Type_Node
{
    /**
     * @readonly
     */
    private bool $is_wrapped_in_brackets = \false;
    /**
     * @param TypeNode[] $types
     */
    public function __construct(array $types, bool $is_wrapped_in_brackets = \false)
    {
        $this->is_wrapped_in_brackets = $is_wrapped_in_brackets;
        parent::__construct($types);
    }
    /**
     * Preserve common format
     */
    #[Override]
    public function __toString(): string
    {
        $types = [];
        // get the actual strings first before array_unique
        // to avoid similar object but different printing to be treated as unique
        foreach ($this->types as $type) {
            $types[] = (string) $type;
        }
        $types = array_unique($types);
        if (!$this->is_wrapped_in_brackets) {
            return implode('|', $types);
        }
        return '(' . implode('|', $types) . ')';
    }
    public function is_wrapped_in_brackets(): bool
    {
        return $this->is_wrapped_in_brackets;
    }
}