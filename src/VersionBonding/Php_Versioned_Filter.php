<?php

declare (strict_types=1);
namespace Rector\Version_Bonding;

use Rector\Contract\Rector\Rector_Interface;
use Rector\Php\Php_Version_Provider;
use Rector\Php\Polyfill_Packages_Provider;
use Rector\Version_Bonding\Contract\Min_Php_Version_Interface;
use Rector\Version_Bonding\Contract\Related_Polyfill_Interface;
/**
 * @see \Rector\Tests\VersionBonding\PhpVersionedFilterTest
 */
final class Php_Versioned_Filter
{
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @readonly
     */
    private Polyfill_Packages_Provider $polyfill_packages_provider;
    public function __construct(Php_Version_Provider $php_version_provider, Polyfill_Packages_Provider $polyfill_packages_provider)
    {
        $this->php_version_provider = $php_version_provider;
        $this->polyfill_packages_provider = $polyfill_packages_provider;
    }
    /**
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    public function filter(array $rectors): array
    {
        $min_project_php_version = $this->php_version_provider->provide();
        $active_rectors = [];
        foreach ($rectors as $rector) {
            if ($rector instanceof Related_Polyfill_Interface) {
                $polyfill_package_names = $this->polyfill_packages_provider->provide();
                if (in_array($rector->provide_polyfill_package(), $polyfill_package_names, \true)) {
                    $active_rectors[] = $rector;
                    continue;
                }
            }
            if (!$rector instanceof Min_Php_Version_Interface) {
                $active_rectors[] = $rector;
                continue;
            }
            // does satisfy version? → include
            if ($rector->provide_min_php_version() <= $min_project_php_version) {
                $active_rectors[] = $rector;
            }
        }
        return $active_rectors;
    }
}