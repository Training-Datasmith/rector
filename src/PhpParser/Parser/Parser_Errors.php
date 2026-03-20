<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Parser;

use Php_Stan\Parser\Parser_Errors_Exception;
final class Parser_Errors
{
    /**
     * @readonly
     */
    private string $message;
    /**
     * @readonly
     */
    private int $line;
    public function __construct(Parser_Errors_Exception $parser_errors_exception)
    {
        $this->message = $parser_errors_exception->get_message();
        $this->line = $parser_errors_exception->get_attributes()['startLine'] ?? $parser_errors_exception->get_line();
    }
    public function get_message(): string
    {
        return $this->message;
    }
    public function get_line(): int
    {
        return $this->line;
    }
}