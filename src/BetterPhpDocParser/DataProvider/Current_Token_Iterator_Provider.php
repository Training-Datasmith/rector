<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Data_Provider;

use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Exception\Should_Not_Happen_Exception;
final class Current_Token_Iterator_Provider
{
    private ?Better_Token_Iterator $better_token_iterator = null;
    public function set_better_token_iterator(Better_Token_Iterator $better_token_iterator): void
    {
        $this->better_token_iterator = $better_token_iterator;
    }
    public function provide(): Better_Token_Iterator
    {
        if (!$this->better_token_iterator instanceof Better_Token_Iterator) {
            throw new Should_Not_Happen_Exception();
        }
        return $this->better_token_iterator;
    }
}