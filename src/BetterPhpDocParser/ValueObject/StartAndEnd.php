<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object;

use Rector\Exception\Should_Not_Happen_Exception;
final class Start_And_End
{
    /**
     * @readonly
     */
    private int $start;
    /**
     * @readonly
     */
    private int $end;
    public function __construct(int $start, int $end)
    {
        $this->start = $start;
        $this->end = $end;
        if ($end < $start) {
            throw new Should_Not_Happen_Exception();
        }
    }
    public function get_start(): int
    {
        return $this->start;
    }
    public function get_end(): int
    {
        return $this->end;
    }
}