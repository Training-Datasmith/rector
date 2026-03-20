<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Value_Object;

use Php_Parser\Node\Stmt;
use Php_Parser\Token;
final class Stmts_And_Tokens
{
    /**
     * @var Stmt[]
     * @readonly
     */
    private array $stmts;
    /**
     * @var array<int, Token>
     * @readonly
     */
    private array $tokens;
    /**
     * @param Stmt[] $stmts
     * @param array<int, Token> $tokens
     */
    public function __construct(array $stmts, array $tokens)
    {
        $this->stmts = $stmts;
        $this->tokens = $tokens;
    }
    /**
     * @return Stmt[]
     */
    public function get_stmts(): array
    {
        return $this->stmts;
    }
    /**
     * @return array<int, Token>
     */
    public function get_tokens(): array
    {
        return $this->tokens;
    }
}