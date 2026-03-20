<?php

declare (strict_types=1);
namespace Rector\Value_Object;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Scalar\String_;
final class Sprintf_String_And_Args
{
    /**
     * @readonly
     */
    private String_ $string;
    /**
     * @var Expr[]
     * @readonly
     */
    private array $array_items;
    /**
     * @param Expr[] $arrayItems
     */
    public function __construct(String_ $string, array $array_items)
    {
        $this->string = $string;
        $this->array_items = $array_items;
    }
    /**
     * @return Expr[]
     */
    public function get_array_items(): array
    {
        return $this->array_items;
    }
    public function get_string_value(): string
    {
        return $this->string->value;
    }
}