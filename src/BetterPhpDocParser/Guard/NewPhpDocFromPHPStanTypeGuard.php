<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Guard;

use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
final class New_Php_Doc_From_Php_Stan_Type_Guard
{
    public function is_legal(Type $type): bool
    {
        if ($type instanceof Union_Type) {
            return $this->is_legal_union_type($type);
        }
        return \true;
    }
    private function is_legal_union_type(Union_Type $type): bool
    {
        foreach ($type->get_types() as $union_type) {
            if ($union_type instanceof Mixed_Type) {
                return \false;
            }
        }
        return \true;
    }
}