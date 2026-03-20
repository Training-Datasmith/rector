<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Value_Object;

use Php_Stan\Type\Type;
final class Old_To_New_Type
{
    /**
     * @readonly
     */
    private Type $old_type;
    /**
     * @readonly
     */
    private Type $new_type;
    public function __construct(Type $old_type, Type $new_type)
    {
        $this->old_type = $old_type;
        $this->new_type = $new_type;
    }
    public function get_old_type(): Type
    {
        return $this->old_type;
    }
    public function get_new_type(): Type
    {
        return $this->new_type;
    }
}