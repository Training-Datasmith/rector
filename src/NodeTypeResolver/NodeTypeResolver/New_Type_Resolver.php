<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Class_;
use Php_Stan\Analyser\Scope;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\Type;
use Rector\Enum\Object_Reference;
use Rector\Node_Analyzer\Class_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Php_Stan\Object_Without_Class_Type_With_Parent_Types;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
/**
 * @implements NodeTypeResolverInterface<New_>
 */
final class New_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Class_Analyzer $class_analyzer;
    public function __construct(Node_Name_Resolver $node_name_resolver, Class_Analyzer $class_analyzer)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->class_analyzer = $class_analyzer;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [New_::class];
    }
    /**
     * @param New_ $node
     */
    public function resolve(Node $node): Type
    {
        if ($node->class instanceof Name) {
            $class_name = $this->node_name_resolver->get_name($node->class);
            if (!in_array($class_name, [Object_Reference::SELF, Object_Reference::PARENT], \true)) {
                return new Object_Type($class_name);
            }
        }
        $is_anonymous_class = $this->class_analyzer->is_anonymous_class($node->class);
        if ($is_anonymous_class) {
            return $this->resolve_anonymous_class_type($node);
        }
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            // new node probably
            return new Mixed_Type();
        }
        return $scope->get_type($node);
    }
    private function resolve_anonymous_class_type(New_ $new): Object_Without_Class_Type
    {
        if (!$new->class instanceof Class_) {
            return new Object_Without_Class_Type();
        }
        $direct_parent_types = [];
        /** @var Class_ $class */
        $class = $new->class;
        if ($class->extends instanceof Name) {
            $parent_class = (string) $class->extends;
            $direct_parent_types[] = new Fully_Qualified_Object_Type($parent_class);
        }
        foreach ($class->implements as $implement) {
            $parent_class = (string) $implement;
            $direct_parent_types[] = new Fully_Qualified_Object_Type($parent_class);
        }
        if ($direct_parent_types !== []) {
            return new Object_Without_Class_Type_With_Parent_Types($direct_parent_types);
        }
        return new Object_Without_Class_Type();
    }
}