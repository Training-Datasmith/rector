<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Value_Object\Type;

use Override;
use Php_Stan\Type\Static_Type;
final class Simple_Static_Type extends Static_Type
{
    /**
     * @readonly
     */
    private string $class_name;
    public function __construct(string $class_name)
    {
        $this->class_name = $class_name;
    }
    #[Override]
    public function get_class_name(): string
    {
        return $this->class_name;
    }
}