<?php

declare (strict_types=1);
namespace Rector\Reflection;

use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
final class Method_Reflection_Resolver
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Reflection_Provider $reflection_provider)
    {
        $this->reflection_provider = $reflection_provider;
    }
    /**
     * @param class-string $className
     */
    public function resolve_method_reflection(string $class_name, string $method_name, ?Scope $scope): ?Method_Reflection
    {
        if (!$this->reflection_provider->has_class($class_name)) {
            return null;
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        // better, with support for "@method" annotation methods
        if ($scope instanceof Scope) {
            if ($class_reflection->has_method($method_name)) {
                return $class_reflection->get_method($method_name, $scope);
            }
        } elseif ($class_reflection->has_native_method($method_name)) {
            return $class_reflection->get_native_method($method_name);
        }
        return null;
    }
}