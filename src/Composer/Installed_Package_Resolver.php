<?php

declare (strict_types=1);
namespace Rector\Composer;

use Rector\Composer\Value_Object\Installed_Package;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Skipper\File_System\Path_Normalizer;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Nette\Utils\Json;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\Composer\InstalledPackageResolverTest
 */
final class Installed_Package_Resolver
{
    /**
     * @readonly
     */
    private ?string $project_directory = null;
    /**
     * @var null|InstalledPackage[]
     */
    private ?array $resolved_installed_packages = null;
    public function __construct(?string $project_directory = null)
    {
        $this->project_directory = $project_directory;
        // fallback to root project directory
        if ($project_directory === null) {
            $project_directory = getcwd();
        }
        Assert::directory($project_directory);
    }
    /**
     * @return InstalledPackage[]
     */
    public function resolve(): array
    {
        // already cached, even only empty array
        if ($this->resolved_installed_packages !== null) {
            return $this->resolved_installed_packages;
        }
        $installed_packages_file_path = $this->resolve_vendor_dir() . '/composer/installed.json';
        if (!file_exists($installed_packages_file_path)) {
            throw new Should_Not_Happen_Exception('The installed package json not found. Make sure you run `composer update` and the "vendor/composer/installed.json" file exists');
        }
        $installed_package_file_contents = File_System::read($installed_packages_file_path);
        $installed_packages_file_path = Json::decode($installed_package_file_contents, \true);
        $installed_packages = $this->create_installed_packages($installed_packages_file_path['packages']);
        $this->resolved_installed_packages = $installed_packages;
        return $installed_packages;
    }
    public function resolve_package_version(string $package_name): ?string
    {
        $installed_packages = $this->resolve();
        foreach ($installed_packages as $installed_package) {
            if ($installed_package->get_name() !== $package_name) {
                continue;
            }
            return $installed_package->get_version();
        }
        return null;
    }
    /**
     * @param mixed[] $packages
     * @return InstalledPackage[]
     */
    private function create_installed_packages(array $packages): array
    {
        $installed_packages = [];
        foreach ($packages as $package) {
            $installed_packages[] = new Installed_Package($package['name'], $package['version_normalized']);
        }
        return $installed_packages;
    }
    private function resolve_vendor_dir(): string
    {
        $project_composer_json_file_path = $this->project_directory . '/composer.json';
        if (\file_exists($project_composer_json_file_path)) {
            $project_composer_contents = File_System::read($project_composer_json_file_path);
            $project_composer_json = Json::decode($project_composer_contents, \true);
            if (isset($project_composer_json['config']['vendor-dir']) && is_string($project_composer_json['config']['vendor-dir'])) {
                $real_path_vendor_dir = realpath($project_composer_json['config']['vendor-dir']) ?: '';
                $normalized_real_path_vendor_dir = Path_Normalizer::normalize($real_path_vendor_dir);
                $normalized_vendor_dir = Path_Normalizer::normalize($project_composer_json['config']['vendor-dir']);
                return $normalized_real_path_vendor_dir === $normalized_vendor_dir ? $project_composer_json['config']['vendor-dir'] : $this->project_directory . '/' . $project_composer_json['config']['vendor-dir'];
            }
        }
        return $this->project_directory . '/vendor';
    }
}