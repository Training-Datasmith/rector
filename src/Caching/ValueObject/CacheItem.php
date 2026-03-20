<?php

declare (strict_types=1);
namespace Rector\Caching\Value_Object;

/**
 * Inspired by
 * https://github.com/phpstan/phpstan-src/commit/eeae2da7999b2e8b7b04542c6175d46f80c6d0b9#diff-6dc14f6222bf150e6840ca44a7126653052a1cedc6a149b4e5c1e1a2c80eacdc
 */
final class Cache_Item
{
    /**
     * @readonly
     */
    private string $variable_key;
    /**
     * @readonly
     * @var mixed
     */
    private $data;
    /**
     * @param mixed $data
     */
    public function __construct(string $variable_key, $data)
    {
        $this->variable_key = $variable_key;
        $this->data = $data;
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['variableKey'], $properties['data']);
    }
    public function is_variable_key_valid(string $variable_key): bool
    {
        return $this->variable_key === $variable_key;
    }
    /**
     * @return mixed
     */
    public function get_data()
    {
        return $this->data;
    }
}