<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Value_Object;

use Php_Parser\Modifiers;
use Php_Stan\Type\Type;
final class Property_Metadata
{
    /**
     * @readonly
     */
    private string $name;
    /**
     * @readonly
     */
    private ?Type $type;
    /**
     * @readonly
     */
    private int $flags = Modifiers::PRIVATE;
    public function __construct(string $name, ?Type $type, int $flags = Modifiers::PRIVATE)
    {
        $this->name = $name;
        $this->type = $type;
        $this->flags = $flags;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_type(): ?Type
    {
        return $this->type;
    }
    public function get_flags(): int
    {
        return $this->flags;
    }
}