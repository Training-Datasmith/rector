<?php

declare (strict_types=1);
namespace Rector\Configuration;

use Rector\Contract\Rector\Rector_Interface;
use Rector\Value_Object\Configuration;
/**
 * Modify available rector rules based on the configuration options
 */
final class Configuration_Rule_Filter
{
    private ?Configuration $configuration = null;
    public function set_configuration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }
    /**
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    public function filter(array $rectors): array
    {
        if (!$this->configuration instanceof Configuration) {
            return $rectors;
        }
        $only_rule = $this->configuration->get_only_rule();
        if ($only_rule !== null) {
            return $this->filter_only_rule($rectors, $only_rule);
        }
        return $rectors;
    }
    /**
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    public function filter_only_rule(array $rectors, string $only_rule): array
    {
        $active_rectors = [];
        foreach ($rectors as $rector) {
            if ($rector instanceof $only_rule) {
                $active_rectors[] = $rector;
            }
        }
        return $active_rectors;
    }
}