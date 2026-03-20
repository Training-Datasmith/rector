<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Info;

use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Php_Stan\Php_Doc_Parser\Parser\Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
final class Token_Iterator_Factory
{
    /**
     * @readonly
     */
    private Lexer $lexer;
    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }
    public function create(string $content): Better_Token_Iterator
    {
        $tokens = $this->lexer->tokenize($content);
        return new Better_Token_Iterator($tokens);
    }
    public function create_from_token_iterator(Token_Iterator $token_iterator): Better_Token_Iterator
    {
        if ($token_iterator instanceof Better_Token_Iterator) {
            return $token_iterator;
        }
        // keep original tokens and index position
        $tokens = $token_iterator->get_tokens();
        $current_index = $token_iterator->current_token_index();
        return new Better_Token_Iterator($tokens, $current_index);
    }
}