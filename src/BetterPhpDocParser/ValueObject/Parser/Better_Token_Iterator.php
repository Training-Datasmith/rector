<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Parser;

use Php_Stan\Php_Doc_Parser\Parser\Token_Iterator;
use Rector\Exception\Should_Not_Happen_Exception;
final class Better_Token_Iterator extends Token_Iterator
{
    /**
     * @param array<int, mixed> $tokens
     */
    public function __construct(array $tokens, int $index = 0)
    {
        if ($tokens === []) {
            $index = 0;
        }
        parent::__construct($tokens, $index);
    }
    /**
     * @param int[] $types
     */
    public function is_next_token_types(array $types): bool
    {
        foreach ($types as $type) {
            if ($this->is_next_token_type($type)) {
                return \true;
            }
        }
        return \false;
    }
    public function is_token_type_on_position(int $token_type, int $position): bool
    {
        $tokens = $this->get_tokens();
        $token = $tokens[$position] ?? null;
        if ($token === null) {
            return \false;
        }
        return $token[1] === $token_type;
    }
    public function is_next_token_type(int $token_type): bool
    {
        if ($this->next_token_type() === null) {
            return \false;
        }
        return $this->next_token_type() === $token_type;
    }
    public function print_from_to(int $from, int $to): string
    {
        if ($to < $from) {
            throw new Should_Not_Happen_Exception('Arguments are flipped');
        }
        $tokens = $this->get_tokens();
        $content = '';
        foreach ($tokens as $key => $token) {
            if ($key < $from) {
                continue;
            }
            if ($key >= $to) {
                continue;
            }
            $content .= $token[0];
        }
        return $content;
    }
    public function current_position(): int
    {
        return $this->current_token_index();
    }
    public function count(): int
    {
        return count($this->get_tokens());
    }
    /**
     * @return array<array{0: string, 1: int}>
     */
    public function partial_tokens(int $start, int $end): array
    {
        return array_slice($this->get_tokens(), $start, $end - $start + 1);
    }
    public function contains_token_type(int $type): bool
    {
        foreach ($this->get_tokens() as $token) {
            if ($token[1] === $type) {
                return \true;
            }
        }
        return \false;
    }
    private function next_token_type(): ?int
    {
        $tokens = $this->get_tokens();
        // does next token exist?
        $next_index = $this->current_position() + 1;
        if (!isset($tokens[$next_index])) {
            return null;
        }
        $this->push_save_point();
        $this->next();
        $next_token_type = $this->current_token_type();
        $this->rollback();
        return $next_token_type;
    }
}