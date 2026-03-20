<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Php_Doc_Parser\Ast\Node as AstNode;
use Php_Stan\Php_Doc_Parser\Ast\Type\Array_Shape_Item_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Union_Type_Node;
use Php_Stan\Type\Intersection_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\Type;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Static_Type_Mapper\Mapper\Scalar_String_To_Type_Mapper;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @implements TypeMapperInterface<IntersectionType>
 */
final class Intersection_Type_Mapper implements Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @readonly
     */
    private \Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Without_Class_Type_Mapper $object_without_class_type_mapper;
    /**
     * @readonly
     */
    private \Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Type_Mapper $object_type_mapper;
    /**
     * @readonly
     */
    private Scalar_String_To_Type_Mapper $scalar_string_to_type_mapper;
    public function __construct(Php_Version_Provider $php_version_provider, \Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Without_Class_Type_Mapper $object_without_class_type_mapper, \Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Type_Mapper $object_type_mapper, Scalar_String_To_Type_Mapper $scalar_string_to_type_mapper)
    {
        $this->php_version_provider = $php_version_provider;
        $this->object_without_class_type_mapper = $object_without_class_type_mapper;
        $this->object_type_mapper = $object_type_mapper;
        $this->scalar_string_to_type_mapper = $scalar_string_to_type_mapper;
    }
    public function get_node_class(): string
    {
        return Intersection_Type::class;
    }
    /**
     * @param IntersectionType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        $type_node = $type->to_php_doc_node();
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $php_doc_node_traverser->traverse_with_callable($type_node, '', function (Ast_Node $ast_node) {
            if ($ast_node instanceof Union_Type_Node) {
                return Php_Doc_Node_Traverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($ast_node instanceof Array_Shape_Item_Node) {
                return Php_Doc_Node_Traverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$ast_node instanceof Identifier_Type_Node) {
                return Php_Doc_Node_Traverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            $type = $this->scalar_string_to_type_mapper->map_scalar_string_to_type($ast_node->name);
            if ($type->is_scalar()->yes()) {
                return Php_Doc_Node_Traverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($type->is_array()->yes()) {
                return Php_Doc_Node_Traverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($type instanceof Mixed_Type && $type->is_explicit_mixed()) {
                return Php_Doc_Node_Traverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            $ast_node->name = '\\' . ltrim($ast_node->name, '\\');
            return $ast_node;
        });
        return $type_node;
    }
    /**
     * @param IntersectionType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::INTERSECTION_TYPES)) {
            return null;
        }
        $intersectioned_type_nodes = [];
        foreach ($type->get_types() as $type) {
            if ($type instanceof Object_Without_Class_Type) {
                return $this->object_without_class_type_mapper->map_to_php_parser_node($type, $type_kind);
            }
            if (!$type instanceof Object_Type) {
                return null;
            }
            $resolved_type = $this->object_type_mapper->map_to_php_parser_node($type, $type_kind);
            if (!$resolved_type instanceof Fully_Qualified) {
                return null;
            }
            $intersectioned_type_nodes[] = $resolved_type;
        }
        if ($intersectioned_type_nodes === []) {
            return null;
        }
        if (count($intersectioned_type_nodes) === 1) {
            return current($intersectioned_type_nodes);
        }
        if ($type_kind === Type_Kind::UNION && !$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::UNION_INTERSECTION_TYPES)) {
            return null;
        }
        return new Node\Intersection_Type($intersectioned_type_nodes);
    }
}