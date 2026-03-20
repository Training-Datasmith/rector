<?php

declare (strict_types=1);
namespace Rector\Set;

use Rector\Bridge\Set_Provider_Collector;
use Rector\Composer\Installed_Package_Resolver;
use Rector\Set\Enum\Set_Group;
use Rector\Set\Value_Object\Composer_Triggered_Set;
/**
 * @see \Rector\Tests\Set\SetManager\SetManagerTest
 */
final class Set_Manager
{
    /**
     * @readonly
     */
    private Set_Provider_Collector $set_provider_collector;
    /**
     * @readonly
     */
    private Installed_Package_Resolver $installed_package_resolver;
    public function __construct(Set_Provider_Collector $set_provider_collector, Installed_Package_Resolver $installed_package_resolver)
    {
        $this->set_provider_collector = $set_provider_collector;
        $this->installed_package_resolver = $installed_package_resolver;
    }
    /**
     * @return ComposerTriggeredSet[]
     */
    public function match_composer_triggered(string $group_name): array
    {
        $matched_sets = [];
        foreach ($this->set_provider_collector->provide_composer_triggered_sets() as $composer_triggered_set) {
            if ($composer_triggered_set->get_group_name() === $group_name) {
                $matched_sets[] = $composer_triggered_set;
            }
        }
        return $matched_sets;
    }
    /**
     * @param SetGroup::*[] $setGroups
     * @return string[]
     */
    public function match_by_set_groups(array $set_groups): array
    {
        $installed_composer_packages = $this->installed_package_resolver->resolve();
        $group_loaded_sets = [];
        foreach ($set_groups as $set_group) {
            $composer_triggered_sets = $this->match_composer_triggered($set_group);
            foreach ($composer_triggered_sets as $composer_triggered_set) {
                if ($composer_triggered_set->match_installed_packages($installed_composer_packages)) {
                    // it matched composer package + version requirements → load set
                    $group_loaded_sets[] = realpath($composer_triggered_set->get_set_file_path());
                }
            }
        }
        return $group_loaded_sets;
    }
}