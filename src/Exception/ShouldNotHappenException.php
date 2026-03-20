<?php

declare (strict_types=1);
namespace Rector\Exception;

use Exception;
use Throwable;
final class Should_Not_Happen_Exception extends Exception
{
    /**
     * @param int $code
     */
    public function __construct(string $message = '', $code = 0, ?Throwable $throwable = null)
    {
        if ($message === '') {
            $message = $this->create_default_message_with_location();
        }
        parent::__construct($message, $code, $throwable);
    }
    private function create_default_message_with_location(): string
    {
        $debug_backtrace = debug_backtrace();
        $class = $debug_backtrace[2]['class'] ?? null;
        $function = $debug_backtrace[2]['function'];
        $line = $debug_backtrace[1]['line'] ?? 0;
        $method = $class !== null ? $class . '::' . $function : $function;
        /** @var string $method */
        /** @var int $line */
        return sprintf('Look at "%s()" on line %d', $method, $line);
    }
}