<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan;

use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_With_Class_Name;
final class Object_Without_Class_Type_With_Parent_Types extends Object_Without_Class_Type
{
    /**
     * @var TypeWithClassName[]
     * @readonly
     */
    private array $parent_types;
    /**
     * @param TypeWithClassName[] $parentTypes
     */
    public function __construct(array $parent_types, ?Type $subtracted_type = null)
    {
        $this->parent_types = $parent_types;
        parent::__construct($subtracted_type);
    }
    /**
     * @return TypeWithClassName[]
     */
    public function get_parent_types(): array
    {
        return $this->parent_types;
    }
}