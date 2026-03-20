<?php

declare (strict_types=1);
namespace Rector\Util;

use Rector_Prefix202603\Nette\Utils\Strings;
final class String_Utils
{
    public static function is_match(string $value, string $regex): bool
    {
        $match = Strings::match($value, $regex);
        return $match !== null;
    }
}