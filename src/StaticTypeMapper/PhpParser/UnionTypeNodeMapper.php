<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Union_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Php_Stan\Type\Type_Factory;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
/**
 * @implements PhpParserNodeMapperInterface<UnionType>
 */
final class Union_Type_Node_Mapper implements Php_Parser_Node_Mapper_Interface
{
    /**
     * @readonly
     */
    private Type_Factory $type_factory;
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Parser\Fully_Qualified_Node_Mapper $fully_qualified_node_mapper;
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Parser\Name_Node_Mapper $name_node_mapper;
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Parser\Identifier_Node_Mapper $identifier_node_mapper;
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Parser\Intersection_Type_Node_Mapper $intersection_type_node_mapper;
    public function __construct(Type_Factory $type_factory, \Rector\Static_Type_Mapper\Php_Parser\Fully_Qualified_Node_Mapper $fully_qualified_node_mapper, \Rector\Static_Type_Mapper\Php_Parser\Name_Node_Mapper $name_node_mapper, \Rector\Static_Type_Mapper\Php_Parser\Identifier_Node_Mapper $identifier_node_mapper, \Rector\Static_Type_Mapper\Php_Parser\Intersection_Type_Node_Mapper $intersection_type_node_mapper)
    {
        $this->type_factory = $type_factory;
        $this->fully_qualified_node_mapper = $fully_qualified_node_mapper;
        $this->name_node_mapper = $name_node_mapper;
        $this->identifier_node_mapper = $identifier_node_mapper;
        $this->intersection_type_node_mapper = $intersection_type_node_mapper;
    }
    public function get_node_type(): string
    {
        return Union_Type::class;
    }
    /**
     * @param UnionType $node
     */
    public function map_to_php_stan(Node $node): Type
    {
        $types = [];
        foreach ($node->types as $unioned_type) {
            if ($unioned_type instanceof Fully_Qualified) {
                $types[] = $this->fully_qualified_node_mapper->map_to_php_stan($unioned_type);
                continue;
            }
            if ($unioned_type instanceof Name) {
                $types[] = $this->name_node_mapper->map_to_php_stan($unioned_type);
                continue;
            }
            if ($unioned_type instanceof Identifier) {
                $types[] = $this->identifier_node_mapper->map_to_php_stan($unioned_type);
                continue;
            }
            $types[] = $this->intersection_type_node_mapper->map_to_php_stan($unioned_type);
        }
        return $this->type_factory->create_mixed_passed_or_union_type($types, \true);
    }
}