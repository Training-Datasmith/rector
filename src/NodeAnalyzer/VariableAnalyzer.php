<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Expr\Variable;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Variable_Analyzer
{
    public function is_static_or_global(Variable $variable): bool
    {
        if ($variable->get_attribute(Attribute_Key::IS_GLOBAL_VAR) === \true) {
            return \true;
        }
        return $variable->get_attribute(Attribute_Key::IS_STATIC_VAR) === \true;
    }
    public function is_used_by_reference(Variable $variable): bool
    {
        return $variable->get_attribute(Attribute_Key::IS_BYREF_VAR) === \true;
    }
}