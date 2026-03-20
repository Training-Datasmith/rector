<?php

declare (strict_types=1);
namespace Rector\Version_Bonding\Contract;

use Rector\Version_Bonding\Value_Object\Composer_Package_Constraint;
/**
 * Can be implemented by @see \Rector\Contract\Rector\RectorInterface
 *
 * Rules that do not meet this composer package constraint will be skipped.
 *
 * @api used by extensions
 */
interface Composer_Package_Constraint_Interface
{
    public function provide_composer_package_constraint(): Composer_Package_Constraint;
}