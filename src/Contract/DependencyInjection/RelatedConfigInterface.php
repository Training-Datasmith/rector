<?php

declare (strict_types=1);
namespace Rector\Contract\Dependency_Injection;

/**
 * @internal Use for rules that require extra custom services.
 */
interface Related_Config_Interface
{
    public static function get_config_file(): string;
}