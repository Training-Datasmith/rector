<?php

declare (strict_types=1);
namespace Rector\Util;

final class Array_Parameters_Merger
{
    /**
     * Merges configurations. Left has higher priority than right one.
     *
     * @author David Grudl (https://davidgrudl.com)
     * @source https://github.com/nette/di/blob/8eb90721a131262f17663e50aee0032a62d0ef08/src/DI/Config/Helpers.php#L31
     * @param mixed $left
     * @param mixed $right
     * @return mixed
     */
    public function merge($left, $right)
    {
        if (is_array($left) && is_array($right)) {
            return $this->merge_left_to_right_with_callable($left, $right, \Closure::from_callable([$this, 'merge']));
        }
        if ($left !== null) {
            return $left;
        }
        if (!is_array($right)) {
            return $left;
        }
        return $right;
    }
    /**
     * @param array<int|string, mixed> $left
     * @param array<int|string, mixed> $right
     * @return mixed[]
     */
    private function merge_left_to_right_with_callable(array $left, array $right, callable $merge_callback): array
    {
        foreach ($left as $key => $val) {
            if (is_int($key)) {
                // prevent duplicated values in unindexed arrays
                if (!in_array($val, $right, \true)) {
                    $right[] = $val;
                }
            } else {
                if (isset($right[$key])) {
                    $val = $merge_callback($val, $right[$key]);
                }
                $right[$key] = $val;
            }
        }
        return $right;
    }
}