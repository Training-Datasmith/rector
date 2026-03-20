<?php

declare (strict_types=1);
namespace Rector\Testing\Php_Unit;

final class Static_Php_Unit_Environment
{
    /**
     * Never ever used static methods if possible, this is just handy for tests + src to prevent duplication.
     */
    public static function is_php_unit_run(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL');
    }
}