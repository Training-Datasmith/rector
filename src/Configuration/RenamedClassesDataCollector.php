<?php

declare (strict_types=1);
namespace Rector\Configuration;

use Php_Stan\Type\Object_Type;
use Rector\Contract\Dependency_Injection\Resettable_Interface;
final class Renamed_Classes_Data_Collector implements Resettable_Interface
{
    /**
     * @var array<string, string>
     */
    private array $old_to_new_classes = [];
    public function reset(): void
    {
        $this->old_to_new_classes = [];
    }
    /**
     * keep public modifier and use internally on matchClassName() method
     * to keep API as on Configuration level
     */
    public function has_old_class(string $old_class): bool
    {
        return isset($this->old_to_new_classes[$old_class]);
    }
    /**
     * @param array<string, string> $oldToNewClasses
     */
    public function add_old_to_new_classes(array $old_to_new_classes): void
    {
        /** @var array<string, string> $oldToNewClasses */
        $old_to_new_classes = array_merge($this->old_to_new_classes, $old_to_new_classes);
        $this->old_to_new_classes = $old_to_new_classes;
    }
    /**
     * @return array<string, string>
     */
    public function get_old_to_new_classes(): array
    {
        return $this->old_to_new_classes;
    }
    public function match_class_name(Object_Type $object_type): ?Object_Type
    {
        $class_name = $object_type->get_class_name();
        if (!$this->has_old_class($class_name)) {
            return null;
        }
        return new Object_Type($this->old_to_new_classes[$class_name]);
    }
    /**
     * @return string[]
     */
    public function get_old_classes(): array
    {
        return array_keys($this->old_to_new_classes);
    }
}