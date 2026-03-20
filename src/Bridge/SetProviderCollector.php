<?php

declare (strict_types=1);
namespace Rector\Bridge;

use Rector\Doctrine\Set\Set_Provider\Doctrine_Set_Provider;
use Rector\Php_Unit\Set\Set_Provider\Php_Unit_Set_Provider;
use Rector\Set\Contract\Set_Interface;
use Rector\Set\Contract\Set_Provider_Interface;
use Rector\Set\Set_Provider\Core_Set_Provider;
use Rector\Set\Set_Provider\Php_Set_Provider;
use Rector\Set\Value_Object\Composer_Triggered_Set;
use Rector\Symfony\Set\Set_Provider\Symfony3set_Provider;
use Rector\Symfony\Set\Set_Provider\Symfony4set_Provider;
use Rector\Symfony\Set\Set_Provider\Symfony5set_Provider;
use Rector\Symfony\Set\Set_Provider\Symfony6set_Provider;
use Rector\Symfony\Set\Set_Provider\Symfony7set_Provider;
use Rector\Symfony\Set\Set_Provider\Symfony_Set_Provider;
use Rector\Symfony\Set\Set_Provider\Twig_Set_Provider;
/**
 * @api
 *
 * Utils class to ease building bridges by 3rd-party tools
 */
final class Set_Provider_Collector
{
    /**
     * @var SetProviderInterface[]
     * @readonly
     */
    private array $set_providers;
    /**
     * @param SetProviderInterface[] $extraSetProviders
     */
    public function __construct(array $extra_set_providers = [])
    {
        $set_providers = [
            // register all known set providers here
            new Php_Set_Provider(),
            new Core_Set_Provider(),
            new Php_Unit_Set_Provider(),
            new Symfony_Set_Provider(),
            new Symfony3set_Provider(),
            new Symfony4set_Provider(),
            new Symfony5set_Provider(),
            new Symfony6set_Provider(),
            new Symfony7set_Provider(),
            new Doctrine_Set_Provider(),
            new Twig_Set_Provider(),
        ];
        $this->set_providers = array_merge($set_providers, $extra_set_providers);
    }
    /**
     * @return array<SetProviderInterface>
     */
    public function provide(): array
    {
        return $this->set_providers;
    }
    /**
     * @return array<SetInterface>
     */
    public function provide_sets(): array
    {
        $sets = [];
        foreach ($this->set_providers as $set_provider) {
            $sets = array_merge($sets, $set_provider->provide());
        }
        return $sets;
    }
    /**
     * @return array<ComposerTriggeredSet>
     */
    public function provide_composer_triggered_sets(): array
    {
        return array_filter($this->provide_sets(), fn(Set_Interface $set): bool => $set instanceof Composer_Triggered_Set);
    }
}