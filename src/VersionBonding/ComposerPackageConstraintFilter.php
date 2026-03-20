<?php

declare (strict_types=1);
namespace Rector\Version_Bonding;

use Rector\Composer\Installed_Package_Resolver;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Version_Bonding\Contract\Composer_Package_Constraint_Interface;
use Rector_Prefix202603\Composer\Semver\Semver;
/**
 * @see \Rector\Tests\VersionBonding\ComposerPackageConstraintFilterTest
 */
final class Composer_Package_Constraint_Filter
{
    /**
     * @readonly
     */
    private Installed_Package_Resolver $installed_package_resolver;
    public function __construct(Installed_Package_Resolver $installed_package_resolver)
    {
        $this->installed_package_resolver = $installed_package_resolver;
    }
    /**
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    public function filter(array $rectors): array
    {
        $active_rectors = [];
        foreach ($rectors as $rector) {
            if (!$rector instanceof Composer_Package_Constraint_Interface) {
                $active_rectors[] = $rector;
                continue;
            }
            if ($this->satisfies_composer_package_constraint($rector)) {
                $active_rectors[] = $rector;
            }
        }
        return $active_rectors;
    }
    private function satisfies_composer_package_constraint(Composer_Package_Constraint_Interface $rector): bool
    {
        $composer_package_constraint = $rector->provide_composer_package_constraint();
        $package_version = $this->installed_package_resolver->resolve_package_version($composer_package_constraint->get_package_name());
        if ($package_version === null) {
            return \false;
        }
        return Semver::satisfies($package_version, $composer_package_constraint->get_constraint());
    }
}