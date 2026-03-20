<?php

declare (strict_types=1);
namespace Rector\Set\Value_Object;

use Rector\Composer\Value_Object\Installed_Package;
use Rector\Set\Contract\Set_Interface;
use Rector_Prefix202603\Composer\Semver\Semver;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @api used by extensions
 */
final class Composer_Triggered_Set implements Set_Interface
{
    /**
     * @readonly
     */
    private string $group_name;
    /**
     * @readonly
     */
    private string $package_name;
    /**
     * @readonly
     */
    private string $version;
    /**
     * @readonly
     */
    private string $set_file_path;
    /**
     * @see https://regex101.com/r/ioYomu/1
     * @var string
     */
    private const PACKAGE_REGEX = '#^[a-z0-9-]+\/([a-z0-9-_]+|\*)$#';
    public function __construct(string $group_name, string $package_name, string $version, string $set_file_path)
    {
        $this->group_name = $group_name;
        $this->package_name = $package_name;
        $this->version = $version;
        $this->set_file_path = $set_file_path;
        Assert::regex($this->package_name, self::PACKAGE_REGEX);
        Assert::file_exists($set_file_path);
    }
    public function get_group_name(): string
    {
        return $this->group_name;
    }
    public function get_set_file_path(): string
    {
        return $this->set_file_path;
    }
    /**
     * @param InstalledPackage[] $installedPackages
     */
    public function match_installed_packages(array $installed_packages): bool
    {
        foreach ($installed_packages as $installed_package) {
            if ($installed_package->get_name() !== $this->package_name) {
                continue;
            }
            return Semver::satisfies($installed_package->get_version(), '^' . $this->version);
        }
        return \false;
    }
    public function get_name(): string
    {
        return $this->package_name . ' ' . $this->version;
    }
}