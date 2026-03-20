<?php

declare (strict_types=1);
namespace Rector\Php;

use Php_Stan\Analyser\Scope;
final class Reserved_Keyword_Analyzer
{
    public function is_native_variable(string $name): bool
    {
        return in_array($name, Scope::SUPERGLOBAL_VARIABLES, \true);
    }
}