<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Value_Object\Type;

use Override;
use Php_Stan\Type\Generic\Generic_Object_Type;
use Php_Stan\Type\Is_Super_Type_Of_Result;
use Php_Stan\Type\Type;
/**
 * @api
 */
final class Shortened_Generic_Object_Type extends Generic_Object_Type
{
    /**
     * @var class-string
     * @readonly
     */
    private string $fully_qualified_name;
    /**
     * @param class-string $fullyQualifiedName
     */
    public function __construct(string $short_name, array $types, string $fully_qualified_name)
    {
        $this->fully_qualified_name = $fully_qualified_name;
        parent::__construct($short_name, $types);
    }
    #[Override]
    public function is_super_type_of(Type $type): Is_Super_Type_Of_Result
    {
        $generic_object_type = new Generic_Object_Type($this->fully_qualified_name, $this->get_types());
        return $generic_object_type->is_super_type_of($type);
    }
    public function get_short_name(): string
    {
        return $this->get_class_name();
    }
}