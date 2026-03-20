<?php

declare (strict_types=1);
namespace Rector\Version_Bonding\Value_Object;

/**
 * @api used by extensions
 */
final class Composer_Package_Constraint
{
    /**
     * @readonly
     */
    private string $package_name;
    /**
     * @readonly
     */
    private string $constraint;
    public function __construct(string $package_name, string $constraint)
    {
        $this->package_name = $package_name;
        $this->constraint = $constraint;
    }
    public function get_package_name(): string
    {
        return $this->package_name;
    }
    public function get_constraint(): string
    {
        return $this->constraint;
    }
}