<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Finder;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Php_Parser\Node\Better_Node_Finder;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
final class Local_Method_Call_Finder
{
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Better_Node_Finder $better_node_finder, Node_Type_Resolver $node_type_resolver, Node_Name_Resolver $node_name_resolver)
    {
        $this->better_node_finder = $better_node_finder;
        $this->node_type_resolver = $node_type_resolver;
        $this->node_name_resolver = $node_name_resolver;
    }
    /**
     * @return MethodCall[]|StaticCall[]
     */
    public function match(Class_ $class, Class_Method $class_method): array
    {
        $class_name = $this->node_name_resolver->get_name($class);
        if (!is_string($class_name)) {
            return [];
        }
        $class_method_name = $this->node_name_resolver->get_name($class_method);
        /** @var MethodCall[]|StaticCall[] $matchingMethodCalls */
        $matching_method_calls = $this->better_node_finder->find($class->get_methods(), function (Node $sub_node) use ($class_name, $class_method_name): bool {
            if (!$sub_node instanceof Method_Call && !$sub_node instanceof Static_Call) {
                return \false;
            }
            if (!$this->node_name_resolver->is_name($sub_node->name, $class_method_name)) {
                return \false;
            }
            $caller_type = $sub_node instanceof Method_Call ? $this->node_type_resolver->get_type($sub_node->var) : $this->node_type_resolver->get_type($sub_node->class);
            return Class_Name_From_Object_Type_Resolver::resolve($caller_type) === $class_name;
        });
        return $matching_method_calls;
    }
}