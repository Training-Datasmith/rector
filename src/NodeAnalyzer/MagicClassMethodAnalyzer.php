<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Stmt\Class_Method;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Value_Object\Method_Name;
final class Magic_Class_Method_Analyzer
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver)
    {
        $this->node_name_resolver = $node_name_resolver;
    }
    public function is_unsafe_overridden(Class_Method $class_method): bool
    {
        if ($this->node_name_resolver->is_name($class_method, Method_Name::INVOKE)) {
            return \false;
        }
        return $class_method->is_magic();
    }
}