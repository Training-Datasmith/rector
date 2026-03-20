<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Value_Object\Type;

use Override;
use Php_Stan\Type\Is_Super_Type_Of_Result;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
/**
 * @api
 */
final class Shortened_Object_Type extends Object_Type
{
    /**
     * @var class-string
     * @readonly
     */
    private string $fully_qualified_name;
    /**
     * @param class-string $fullyQualifiedName
     */
    public function __construct(string $short_name, string $fully_qualified_name)
    {
        $this->fully_qualified_name = $fully_qualified_name;
        parent::__construct($short_name);
    }
    #[Override]
    public function is_super_type_of(Type $type): Is_Super_Type_Of_Result
    {
        $fully_qualified_object_type = new Object_Type($this->fully_qualified_name);
        return $fully_qualified_object_type->is_super_type_of($type);
    }
    public function get_short_name(): string
    {
        return $this->get_class_name();
    }
    /**
     * @return class-string
     */
    public function get_fully_qualified_name(): string
    {
        return $this->fully_qualified_name;
    }
}