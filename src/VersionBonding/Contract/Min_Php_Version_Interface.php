<?php

declare (strict_types=1);
namespace Rector\Version_Bonding\Contract;

use Rector\Value_Object\Php_Version;
/**
 * Can be implemented by @see \Rector\Contract\Rector\RectorInterface
 *
 * Rules that do not meet this PHP version will be skipped.
 */
interface Min_Php_Version_Interface
{
    /**
     * @return PhpVersion::*
     */
    public function provide_min_php_version(): int;
}