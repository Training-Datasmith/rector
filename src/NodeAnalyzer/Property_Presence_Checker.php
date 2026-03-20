<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Property;
use Rector\Code_Quality\Value_Object\Defined_Property_With_Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Php80\Node_Analyzer\Promoted_Property_Resolver;
/**
 * Can be local property, parent property etc.
 */
final class Property_Presence_Checker
{
    /**
     * @readonly
     */
    private Promoted_Property_Resolver $promoted_property_resolver;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Promoted_Property_Resolver $promoted_property_resolver, Node_Name_Resolver $node_name_resolver)
    {
        $this->promoted_property_resolver = $promoted_property_resolver;
        $this->node_name_resolver = $node_name_resolver;
    }
    /**
     * Includes parent classes and traits
     */
    public function has_class_context_property(Class_ $class, Defined_Property_With_Type $defined_property_with_type): bool
    {
        $property_or_param = $this->get_class_context_property($class, $defined_property_with_type);
        return $property_or_param !== null;
    }
    /**
     * @param \Rector\CodeQuality\ValueObject\DefinedPropertyWithType|\Rector\PostRector\ValueObject\PropertyMetadata $definedPropertyWithType
     * @return \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param|null
     */
    public function get_class_context_property(Class_ $class, $defined_property_with_type)
    {
        $class_name = $this->node_name_resolver->get_name($class);
        if ($class_name === null) {
            return null;
        }
        $property = $class->get_property($defined_property_with_type->get_name());
        if ($property instanceof Property) {
            return $property;
        }
        $promoted_property_params = $this->promoted_property_resolver->resolve_from_class($class);
        foreach ($promoted_property_params as $promoted_property_param) {
            if ($this->node_name_resolver->is_name($promoted_property_param, $defined_property_with_type->get_name())) {
                return $promoted_property_param;
            }
        }
        return null;
    }
}