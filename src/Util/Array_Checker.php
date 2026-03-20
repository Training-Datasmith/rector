<?php

declare (strict_types=1);
namespace Rector\Util;

final class Array_Checker
{
    /**
     * @param mixed[] $elements
     * @param callable(mixed $element): bool $callable
     */
    public function does_exist(array $elements, callable $callable): bool
    {
        foreach ($elements as $element) {
            $is_found = $callable($element);
            if ($is_found) {
                return \true;
            }
        }
        return \false;
    }
}