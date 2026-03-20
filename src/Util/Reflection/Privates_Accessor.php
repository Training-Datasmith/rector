<?php

declare (strict_types=1);
namespace Rector\Util\Reflection;

use Rector\Exception\Reflection\Missing_Private_Property_Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
/**
 * @see \Rector\Tests\Util\Reflection\PrivatesAccessorTest
 */
final class Privates_Accessor
{
    /**
     * @param callable(mixed $value): mixed $closure
     */
    public function property_closure(object $object, string $property_name, callable $closure): void
    {
        $property = $this->get_private_property($object, $property_name);
        // modify value
        $property = $closure($property);
        $this->set_private_property($object, $property_name, $property);
    }
    /**
     * @param object|class-string $object
     * @param mixed[] $arguments
     * @api
     * @return mixed
     */
    public function call_private_method($object, string $method_name, array $arguments)
    {
        if (is_string($object)) {
            $reflection_class = new ReflectionClass($object);
            $object = $reflection_class->new_instance_without_constructor();
        }
        $reflection_method = $this->create_accessible_method_reflection($object, $method_name);
        return $reflection_method->invoke_args($object, $arguments);
    }
    /**
     * @return mixed
     */
    public function get_private_property(object $object, string $property_name)
    {
        $reflection_property = $this->resolve_property_reflection($object, $property_name);
        return $reflection_property->get_value($object);
    }
    /**
     * @param mixed $value
     */
    public function set_private_property(object $object, string $property_name, $value): void
    {
        $reflection_property = $this->resolve_property_reflection($object, $property_name);
        $reflection_property->set_value($object, $value);
    }
    private function create_accessible_method_reflection(object $object, string $method_name): ReflectionMethod
    {
        $reflection = new ReflectionMethod($object, $method_name);
        if (\PHP_VERSION_ID < 80100) {
            $reflection->set_accessible(\true);
        }
        return $reflection;
    }
    private function resolve_property_reflection(object $object, string $property_name): ReflectionProperty
    {
        if (property_exists($object, $property_name)) {
            $reflection = new ReflectionProperty($object, $property_name);
            if (\PHP_VERSION_ID < 80100) {
                $reflection->set_accessible(\true);
            }
            return $reflection;
        }
        $parent_class = get_parent_class($object);
        if ($parent_class !== \false) {
            $reflection = new ReflectionProperty($parent_class, $property_name);
            if (\PHP_VERSION_ID < 80100) {
                $reflection->set_accessible(\true);
            }
            return $reflection;
        }
        $error_message = sprintf('Property "$%s" was not found in "%s" class', $property_name, get_class($object));
        throw new Missing_Private_Property_Exception($error_message);
    }
}