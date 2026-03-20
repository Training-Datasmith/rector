<?php

declare (strict_types=1);
namespace Rector\Node_Nesting_Scope;

use Php_Parser\Node;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Context_Analyzer
{
    /**
     * @api
     */
    public function is_in_loop(Node $node): bool
    {
        return $node->get_attribute(Attribute_Key::IS_IN_LOOP_OR_SWITCH) === \true;
    }
    /**
     * @api
     */
    public function is_in_if(Node $node): bool
    {
        return $node->get_attribute(Attribute_Key::IS_IN_IF) === \true;
    }
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch|\PhpParser\Node\Expr\NullsafePropertyFetch $propertyFetch
     */
    public function is_changeable_context($property_fetch): bool
    {
        if ($property_fetch->get_attribute(Attribute_Key::IS_UNSET_VAR, \false)) {
            return \true;
        }
        if ($property_fetch->get_attribute(Attribute_Key::INSIDE_ARRAY_DIM_FETCH, \false)) {
            return \true;
        }
        if ($property_fetch->get_attribute(Attribute_Key::IS_USED_AS_ARG_BY_REF_VALUE, \false) === \true) {
            return \true;
        }
        return $property_fetch->get_attribute(Attribute_Key::IS_INCREMENT_OR_DECREMENT, \false) === \true;
    }
    public function is_left_part_of_assign(Node $node): bool
    {
        if ($node->get_attribute(Attribute_Key::IS_BEING_ASSIGNED) === \true) {
            return \true;
        }
        if ($node->get_attribute(Attribute_Key::IS_ASSIGN_REF_EXPR) === \true) {
            return \true;
        }
        return $node->get_attribute(Attribute_Key::IS_ASSIGN_OP_VAR) === \true;
    }
}