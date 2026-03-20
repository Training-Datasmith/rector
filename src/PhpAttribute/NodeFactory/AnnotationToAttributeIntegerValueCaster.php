<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Node_Factory;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Parameter_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Php80\Value_Object\Annotation_To_Attribute;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Annotation_To_Attribute_Integer_Value_Caster
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Reflection_Provider $reflection_provider)
    {
        $this->reflection_provider = $reflection_provider;
    }
    /**
     * @param Arg[] $args
     */
    public function cast_attribute_types(Annotation_To_Attribute $annotation_to_attribute, array $args): void
    {
        Assert::all_is_instance_of($args, Arg::class);
        if (!$this->reflection_provider->has_class($annotation_to_attribute->get_attribute_class())) {
            return;
        }
        $attribute_class_reflection = $this->reflection_provider->get_class($annotation_to_attribute->get_attribute_class());
        if (!$attribute_class_reflection->has_constructor()) {
            return;
        }
        $parameter_reflections = $this->resolve_constructor_parameter_reflections($attribute_class_reflection);
        foreach ($parameter_reflections as $parameter_reflection) {
            foreach ($args as $arg) {
                if (!$arg->value instanceof Array_) {
                    continue;
                }
                $array_item = current($arg->value->items) ?: null;
                if (!$array_item instanceof Array_Item) {
                    continue;
                }
                if (!$array_item->key instanceof String_) {
                    continue;
                }
                $key_string = $array_item->key;
                if ($key_string->value !== $parameter_reflection->get_name()) {
                    continue;
                }
                // ensure type is casted to integer
                if (!$array_item->value instanceof String_) {
                    continue;
                }
                if (!$this->contains_integer($parameter_reflection->get_type())) {
                    continue;
                }
                $value_string = $array_item->value;
                if (!is_numeric($value_string->value)) {
                    continue;
                }
                $array_item->value = new Int_((int) $value_string->value);
            }
        }
    }
    private function contains_integer(Type $type): bool
    {
        if ($type->is_integer()->yes()) {
            return \true;
        }
        if (!$type instanceof Union_Type) {
            return \false;
        }
        foreach ($type->get_types() as $unioned_type) {
            if ($unioned_type->is_integer()->yes()) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @return ParameterReflection[]
     */
    private function resolve_constructor_parameter_reflections(Class_Reflection $class_reflection): array
    {
        $extended_method_reflection = $class_reflection->get_constructor();
        $extended_parameters_acceptor = Parameters_Acceptor_Selector::combine_acceptors($extended_method_reflection->get_variants());
        return $extended_parameters_acceptor->get_parameters();
    }
}