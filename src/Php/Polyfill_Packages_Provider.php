<?php

declare (strict_types=1);
namespace Rector\Php;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Value_Object\Polyfill_Package;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Nette\Utils\Json;
final class Polyfill_Packages_Provider
{
    /**
     * @var null|array<int, PolyfillPackage::*>
     */
    private ?array $cached_polyfill_packages = null;
    /**
     * @return array<int, PolyfillPackage::*>
     */
    public function provide(): array
    {
        // disable cache in tests
        if (Simple_Parameter_Provider::has_parameter(Option::POLYFILL_PACKAGES)) {
            return Simple_Parameter_Provider::provide_array_parameter(Option::POLYFILL_PACKAGES);
        }
        // already cached, even only empty array
        if ($this->cached_polyfill_packages !== null) {
            return $this->cached_polyfill_packages;
        }
        $project_composer_json = getcwd() . '/composer.json';
        if (!file_exists($project_composer_json)) {
            $this->cached_polyfill_packages = [];
            return $this->cached_polyfill_packages;
        }
        $composer_contents = File_System::read($project_composer_json);
        $composer_json = Json::decode($composer_contents, \true);
        $this->cached_polyfill_packages = $this->filter_polyfill_packages($composer_json['require'] ?? []);
        return $this->cached_polyfill_packages;
    }
    /**
     * @param array<string, string> $require
     * @return array<int, PolyfillPackage::*>
     */
    private function filter_polyfill_packages(array $require): array
    {
        return array_filter(array_keys($require), static fn(string $package_name): bool => strncmp($package_name, 'symfony/polyfill-', strlen('symfony/polyfill-')) === 0);
    }
}