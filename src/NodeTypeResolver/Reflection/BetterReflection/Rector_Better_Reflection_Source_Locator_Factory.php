<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Reflection\Better_Reflection;

use Php_Stan\Better_Reflection\Source_Locator\Type\Aggregate_Source_Locator;
use Php_Stan\Better_Reflection\Source_Locator\Type\Memoizing_Source_Locator;
use Php_Stan\Reflection\Better_Reflection\Better_Reflection_Source_Locator_Factory;
use Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator\Intermediate_Source_Locator;
/**
 * @api used on phpstan config factory
 */
final class Rector_Better_Reflection_Source_Locator_Factory
{
    /**
     * @readonly
     */
    private Better_Reflection_Source_Locator_Factory $better_reflection_source_locator_factory;
    /**
     * @readonly
     */
    private Intermediate_Source_Locator $intermediate_source_locator;
    public function __construct(Better_Reflection_Source_Locator_Factory $better_reflection_source_locator_factory, Intermediate_Source_Locator $intermediate_source_locator)
    {
        $this->better_reflection_source_locator_factory = $better_reflection_source_locator_factory;
        $this->intermediate_source_locator = $intermediate_source_locator;
    }
    public function create(): Memoizing_Source_Locator
    {
        $php_stan_source_locator = $this->better_reflection_source_locator_factory->create();
        // make PHPStan first source locator, so we avoid parsing every single file - huge performance hit!
        $aggregate_source_locator = new Aggregate_Source_Locator([$php_stan_source_locator, $this->intermediate_source_locator]);
        // important for cache, but should rebuild for tests
        return new Memoizing_Source_Locator($aggregate_source_locator);
    }
}