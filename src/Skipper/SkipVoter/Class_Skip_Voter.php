<?php

declare (strict_types=1);
namespace Rector\Skipper\Skip_Voter;

use Php_Stan\Reflection\Reflection_Provider;
use Rector\Skipper\Skip_Criteria_Resolver\Skipped_Class_Resolver;
use Rector\Skipper\Skipper\Skip_Skipper;
final class Class_Skip_Voter
{
    /**
     * @readonly
     */
    private Skip_Skipper $skip_skipper;
    /**
     * @readonly
     */
    private Skipped_Class_Resolver $skipped_class_resolver;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Skip_Skipper $skip_skipper, Skipped_Class_Resolver $skipped_class_resolver, Reflection_Provider $reflection_provider)
    {
        $this->skip_skipper = $skip_skipper;
        $this->skipped_class_resolver = $skipped_class_resolver;
        $this->reflection_provider = $reflection_provider;
    }
    /**
     * @param string|object $element
     */
    public function match($element): bool
    {
        if (is_object($element)) {
            return \true;
        }
        return $this->reflection_provider->has_class($element);
    }
    /**
     * @param string|object $element
     */
    public function should_skip($element, string $file_path): bool
    {
        $skipped_classes = $this->skipped_class_resolver->resolve();
        return $this->skip_skipper->does_match_skip($element, $file_path, $skipped_classes);
    }
}