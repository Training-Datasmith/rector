<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Trait_;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Object_Type;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
use Rector\Enum\Class_Name;
use Rector\Node_Analyzer\Property_Fetch_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Nesting_Scope\Context_Analyzer;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Php80\Node_Analyzer\Php_Attribute_Analyzer;
use Rector\Php80\Node_Analyzer\Promoted_Property_Resolver;
use Rector\Php_Parser\Ast_Resolver;
use Rector\Php_Parser\Node\Better_Node_Finder;
use Rector\Php_Parser\Node_Finder\Property_Fetch_Finder;
use Rector\Type_Declaration\Already_Assign_Detector\Constructor_Assign_Detector;
use Rector\Value_Object\Method_Name;
use Rector_Prefix202603\Doctrine\ORM\Mapping\Table;
/**
 * For inspiration to improve this service,
 * @see examples of variable modifications in https://wiki.php.net/rfc/readonly_properties_v2#proposal
 */
final class Property_Manipulator
{
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    /**
     * @readonly
     */
    private Property_Fetch_Finder $property_fetch_finder;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Php_Attribute_Analyzer $php_attribute_analyzer;
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @readonly
     */
    private Promoted_Property_Resolver $promoted_property_resolver;
    /**
     * @readonly
     */
    private Constructor_Assign_Detector $constructor_assign_detector;
    /**
     * @readonly
     */
    private Ast_Resolver $ast_resolver;
    /**
     * @readonly
     */
    private Property_Fetch_Analyzer $property_fetch_analyzer;
    /**
     * @readonly
     */
    private Context_Analyzer $context_analyzer;
    /**
     * @var string[]|class-string<Table>[]
     */
    private const ALLOWED_NOT_READONLY_CLASS_ANNOTATIONS = [
        'ApiPlatform\Core\Annotation\ApiResource',
        'ApiPlatform\Metadata\ApiResource',
        'Doctrine\ORM\Mapping\Entity',
        'Doctrine\ORM\Mapping\Table',
        'Doctrine\ORM\Mapping\MappedSuperclass',
        'Doctrine\ORM\Mapping\Embeddable',
        // Deprecated in ODM 2.16
        'Doctrine\ODM\MongoDB\Mapping\Annotations\Document',
        'Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument',
        // New in ODM 2.16
        'Doctrine\ODM\MongoDB\Mapping\Attribute\Document',
        'Doctrine\ODM\MongoDB\Mapping\Attribute\EmbeddedDocument',
    ];
    public function __construct(Better_Node_Finder $better_node_finder, Php_Doc_Info_Factory $php_doc_info_factory, Property_Fetch_Finder $property_fetch_finder, Node_Name_Resolver $node_name_resolver, Php_Attribute_Analyzer $php_attribute_analyzer, Node_Type_Resolver $node_type_resolver, Promoted_Property_Resolver $promoted_property_resolver, Constructor_Assign_Detector $constructor_assign_detector, Ast_Resolver $ast_resolver, Property_Fetch_Analyzer $property_fetch_analyzer, Context_Analyzer $context_analyzer)
    {
        $this->better_node_finder = $better_node_finder;
        $this->php_doc_info_factory = $php_doc_info_factory;
        $this->property_fetch_finder = $property_fetch_finder;
        $this->node_name_resolver = $node_name_resolver;
        $this->php_attribute_analyzer = $php_attribute_analyzer;
        $this->node_type_resolver = $node_type_resolver;
        $this->promoted_property_resolver = $promoted_property_resolver;
        $this->constructor_assign_detector = $constructor_assign_detector;
        $this->ast_resolver = $ast_resolver;
        $this->property_fetch_analyzer = $property_fetch_analyzer;
        $this->context_analyzer = $context_analyzer;
    }
    /**
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param $propertyOrParam
     */
    public function is_property_changeable_except_constructor(Class_ $class, $property_or_param, Scope $scope): bool
    {
        $php_doc_info = $this->php_doc_info_factory->create_from_node_or_empty($class);
        if ($this->has_allowed_not_readonly_annotation_or_attribute($php_doc_info, $class)) {
            return \true;
        }
        if ($this->php_attribute_analyzer->has_php_attribute($property_or_param, Class_Name::JMS_TYPE)) {
            return \true;
        }
        $property_fetches = $this->property_fetch_finder->find_private_property_fetches($class, $property_or_param, $scope);
        $class_method = $class->get_method(Method_Name::CONSTRUCT);
        foreach ($property_fetches as $property_fetch) {
            if ($this->context_analyzer->is_changeable_context($property_fetch)) {
                return \true;
            }
            // skip for constructor? it is allowed to set value in constructor method
            $property_name = (string) $this->node_name_resolver->get_name($property_fetch);
            if ($this->is_property_assigned_only_in_constructor($class, $property_name, $property_fetch, $class_method)) {
                continue;
            }
            if ($this->context_analyzer->is_left_part_of_assign($property_fetch)) {
                return \true;
            }
            if ($property_fetch->get_attribute(Attribute_Key::IS_UNSET_VAR) === \true) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @api Used in rector-symfony
     */
    public function resolve_existing_class_property_name_by_type(Class_ $class, Object_Type $object_type): ?string
    {
        foreach ($class->get_properties() as $property) {
            $property_type = $this->node_type_resolver->get_type($property);
            if (!$property_type->equals($object_type)) {
                continue;
            }
            return $this->node_name_resolver->get_name($property);
        }
        $promoted_property_params = $this->promoted_property_resolver->resolve_from_class($class);
        foreach ($promoted_property_params as $promoted_property_param) {
            $param_type = $this->node_type_resolver->get_type($promoted_property_param);
            if (!$param_type->equals($object_type)) {
                continue;
            }
            return $this->node_name_resolver->get_name($promoted_property_param);
        }
        return null;
    }
    public function is_used_by_trait(Class_Reflection $class_reflection, string $property_name): bool
    {
        foreach ($class_reflection->get_traits() as $trait_use) {
            $trait = $this->ast_resolver->resolve_class_from_class_reflection($trait_use);
            if (!$trait instanceof Trait_) {
                continue;
            }
            if ($this->property_fetch_analyzer->contains_local_property_fetch_name($trait, $property_name)) {
                return \true;
            }
        }
        return \false;
    }
    public function has_trait_with_same_property_or_written(Class_Reflection $class_reflection, string $property_name): bool
    {
        foreach ($class_reflection->get_traits() as $trait_use) {
            if ($trait_use->has_instance_property($property_name) || $trait_use->has_static_property($property_name)) {
                return \true;
            }
            $trait = $this->ast_resolver->resolve_class_from_class_reflection($trait_use);
            if (!$trait instanceof Trait_) {
                continue;
            }
            // is property written to
            if ($this->property_fetch_analyzer->contains_written_property_fetch_name($trait, $property_name)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \PhpParser\Node\Expr\StaticPropertyFetch|\PhpParser\Node\Expr\PropertyFetch $propertyFetch
     */
    private function is_property_assigned_only_in_constructor(Class_ $class, string $property_name, $property_fetch, ?Class_Method $class_method): bool
    {
        if (!$class_method instanceof Class_Method) {
            return \false;
        }
        $node = $this->better_node_finder->find_first((array) $class_method->stmts, static fn(Node $sub_node): bool => ($sub_node instanceof Property_Fetch || $sub_node instanceof Static_Property_Fetch) && $sub_node === $property_fetch);
        // there is property unset in Test class, so only check on __construct
        if (!$node instanceof Node) {
            return \false;
        }
        return $this->constructor_assign_detector->is_property_assigned($class, $property_name);
    }
    private function has_allowed_not_readonly_annotation_or_attribute(Php_Doc_Info $php_doc_info, Class_ $class): bool
    {
        if ($php_doc_info->has_by_annotation_classes(self::ALLOWED_NOT_READONLY_CLASS_ANNOTATIONS)) {
            return \true;
        }
        return $this->php_attribute_analyzer->has_php_attributes($class, self::ALLOWED_NOT_READONLY_CLASS_ANNOTATIONS);
    }
}