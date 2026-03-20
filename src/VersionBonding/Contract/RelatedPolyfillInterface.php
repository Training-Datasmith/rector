<?php

declare (strict_types=1);
namespace Rector\Version_Bonding\Contract;

use Rector\Value_Object\Polyfill_Package;
/**
 * Can be implemented by @see \Rector\Contract\Rector\RectorInterface
 */
interface Related_Polyfill_Interface
{
    /**
     * @return PolyfillPackage::*
     */
    public function provide_polyfill_package(): string;
}