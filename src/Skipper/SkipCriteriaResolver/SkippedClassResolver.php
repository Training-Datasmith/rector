<?php

declare (strict_types=1);
namespace Rector\Skipper\Skip_Criteria_Resolver;

use Rector\Configuration\Deprecation\Contract\Deprecated_Interface;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Testing\Php_Unit\Static_Php_Unit_Environment;
/**
 * @see \Rector\Tests\Skipper\Skipper\SkippedClassResolverTest
 */
final class Skipped_Class_Resolver
{
    /**
     * @var null|array<class-string, string[]|null>
     */
    private ?array $skipped_classes_to_files = null;
    /**
     * @return array<class-string<DeprecatedInterface>>
     */
    public function resolve_deprecated_skipped_classes(): array
    {
        $skipped_class_names = array_keys($this->resolve());
        return array_filter($skipped_class_names, fn(string $class): bool => is_a($class, Deprecated_Interface::class, \true));
    }
    /**
     * @return array<class-string, string[]|null>
     */
    public function resolve(): array
    {
        // disable cache in tests
        if (Static_Php_Unit_Environment::is_php_unit_run()) {
            $this->skipped_classes_to_files = null;
        }
        // already cached, even only empty array
        if ($this->skipped_classes_to_files !== null) {
            return $this->skipped_classes_to_files;
        }
        $skip = Simple_Parameter_Provider::provide_array_parameter(Option::SKIP);
        $this->skipped_classes_to_files = [];
        foreach ($skip as $key => $value) {
            // e.g. [SomeClass::class] → shift values to [SomeClass::class => null]
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }
            if (!is_string($key)) {
                continue;
            }
            // this only checks for Rector rules, that are always autoloaded
            if (!class_exists($key) && !interface_exists($key)) {
                continue;
            }
            $this->skipped_classes_to_files[$key] = $value;
        }
        return $this->skipped_classes_to_files;
    }
}