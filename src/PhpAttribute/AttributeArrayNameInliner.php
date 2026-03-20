<?php

declare (strict_types=1);
namespace Rector\Php_Attribute;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Scalar\Float_;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\String_;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Attribute_Array_Name_Inliner
{
    /**
     * @var class-string
     */
    private const OPEN_API_PROPERTY_ATTRIBUTE = 'OpenApi\Attributes\Property';
    /**
     * @param Array_|list<Arg> $array
     * @return list<Arg>
     */
    public function inline_array_to_args($array, ?string $attribute_class = null): array
    {
        if (is_array($array)) {
            return $this->inline_array($array, $attribute_class);
        }
        return $this->inline_array_node($array);
    }
    /**
     * @return list<Arg>
     */
    private function inline_array_node(Array_ $array): array
    {
        $args = [];
        foreach ($array->items as $array_item) {
            if (!$array_item instanceof Array_Item) {
                continue;
            }
            if ($array_item->key instanceof String_) {
                $string = $array_item->key;
                $argument_name = new Identifier($string->value);
                $args[] = new Arg($array_item->value, \false, \false, [], $argument_name);
            } else {
                $args[] = new Arg($array_item->value);
            }
        }
        return $args;
    }
    /**
     * @param list<Arg> $args
     * @return list<Arg>
     */
    private function inline_array(array $args, ?string $attribute_class = null): array
    {
        Assert::all_is_a_of($args, Arg::class);
        foreach ($args as $arg) {
            if ($attribute_class === self::OPEN_API_PROPERTY_ATTRIBUTE && $arg->name instanceof Identifier && $arg->name->to_string() === 'example') {
                continue;
            }
            if ($arg->value instanceof String_ && is_numeric($arg->value->value)) {
                // use equal over identical on purpose to verify if it is an integer
                if ((float) $arg->value->value == (int) $arg->value->value) {
                    $arg->value = new Int_((int) $arg->value->value);
                } else {
                    $arg->value = new Float_((float) $arg->value->value);
                }
            }
        }
        return $args;
    }
}