<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Corrector;

use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Constant\Constant_String_Type;
use Php_Stan\Type\Generic\Generic_Class_String_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Traverser;
final class Generic_Class_String_Type_Corrector
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Reflection_Provider $reflection_provider)
    {
        $this->reflection_provider = $reflection_provider;
    }
    public function correct(Type $main_type): Type
    {
        // inspired from https://github.com/phpstan/phpstan-src/blob/94e3443b2d21404a821e05b901dd4b57fcbd4e7f/src/Type/Generic/TemplateTypeHelper.php#L18
        return Type_Traverser::map($main_type, function (Type $traversed_type, callable $traverse_callback): Type {
            if (!$traversed_type instanceof Constant_String_Type) {
                return $traverse_callback($traversed_type);
            }
            $value = $traversed_type->get_value();
            if (!$this->reflection_provider->has_class($value)) {
                return $traverse_callback($traversed_type);
            }
            $class_reflection = $this->reflection_provider->get_class($value);
            if ($class_reflection->get_name() !== $value) {
                return $traverse_callback($traversed_type);
            }
            return new Generic_Class_String_Type(new Object_Type($value));
        });
    }
}