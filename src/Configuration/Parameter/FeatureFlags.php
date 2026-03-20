<?php

declare (strict_types=1);
namespace Rector\Configuration\Parameter;

use Php_Parser\Node\Stmt\Class_;
use Rector\Configuration\Option;
/**
 * Class to manage feature flags,
 * that loosen or tighten the behavior of Rector rules.
 */
final class Feature_Flags
{
    public static function treat_classes_as_final(Class_ $class): bool
    {
        // abstract class never can be treated as "final"
        // as always must be overridden
        if ($class->is_abstract()) {
            return \false;
        }
        return \Rector\Configuration\Parameter\Simple_Parameter_Provider::provide_bool_parameter(Option::TREAT_CLASSES_AS_FINAL);
    }
}