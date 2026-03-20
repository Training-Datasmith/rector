<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Expression;
use Php_Stan\Type\Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Php_Parser\Node\Node_Factory;
final class Class_Method_Assign_Manipulator
{
    /**
     * @readonly
     */
    private Node_Factory $node_factory;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @var array<int, string[]>
     */
    private array $already_added_class_method_names = [];
    public function __construct(Node_Factory $node_factory, Node_Name_Resolver $node_name_resolver)
    {
        $this->node_factory = $node_factory;
        $this->node_name_resolver = $node_name_resolver;
    }
    public function add_parameter_and_assign_to_method(Class_Method $class_method, string $name, ?Type $type, Assign $assign): void
    {
        if ($this->has_method_parameter($class_method, $name)) {
            return;
        }
        $class_method->params[] = $this->node_factory->create_param_from_name_and_type($name, $type);
        $class_method->stmts[] = new Expression($assign);
        $class_method_id = spl_object_id($class_method);
        $this->already_added_class_method_names[$class_method_id][] = $name;
    }
    private function has_method_parameter(Class_Method $class_method, string $name): bool
    {
        foreach ($class_method->params as $param) {
            if ($this->node_name_resolver->is_name($param->var, $name)) {
                return \true;
            }
        }
        $class_method_id = spl_object_id($class_method);
        if (!isset($this->already_added_class_method_names[$class_method_id])) {
            return \false;
        }
        return in_array($name, $this->already_added_class_method_names[$class_method_id], \true);
    }
}