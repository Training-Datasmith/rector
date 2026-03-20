<?php

declare (strict_types=1);
namespace Rector\Config;

final class Registered_Service
{
    /**
     * @readonly
     */
    private string $class_name;
    /**
     * @readonly
     */
    private ?string $alias;
    /**
     * @readonly
     */
    private ?string $tag;
    public function __construct(string $class_name, ?string $alias, ?string $tag)
    {
        $this->class_name = $class_name;
        $this->alias = $alias;
        $this->tag = $tag;
    }
    public function get_class_name(): string
    {
        return $this->class_name;
    }
    public function get_alias(): ?string
    {
        return $this->alias;
    }
    public function get_tag(): ?string
    {
        return $this->tag;
    }
}