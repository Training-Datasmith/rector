<?php

declare (strict_types=1);
namespace Rector\Node_Decorator;

use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Expression;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Statement_Depth_Attribute_Decorator
{
    /**
     * @param ClassMethod[] $classMethods
     */
    public static function decorate_class_methods(array $class_methods): void
    {
        foreach ($class_methods as $class_method) {
            foreach ((array) $class_method->stmts as $method_stmt) {
                $method_stmt->set_attribute(Attribute_Key::IS_FIRST_LEVEL_STATEMENT, \true);
                if ($method_stmt instanceof Expression) {
                    $method_stmt->expr->set_attribute(Attribute_Key::IS_FIRST_LEVEL_STATEMENT, \true);
                }
            }
        }
    }
}