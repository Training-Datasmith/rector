<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Type\Intersection_Type;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
/**
 * @implements PhpParserNodeMapperInterface<Node\IntersectionType>
 */
final class Intersection_Type_Node_Mapper implements Php_Parser_Node_Mapper_Interface
{
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
    public function __construct(\Rector\Static_Type_Mapper\Php_Parser\Fully_Qualified_Node_Mapper $fully_qualified_node_mapper, \Rector\Static_Type_Mapper\Php_Parser\Name_Node_Mapper $name_node_mapper, \Rector\Static_Type_Mapper\Php_Parser\Identifier_Node_Mapper $identifier_node_mapper)
    {
        $this->fully_qualified_node_mapper = $fully_qualified_node_mapper;
        $this->name_node_mapper = $name_node_mapper;
        $this->identifier_node_mapper = $identifier_node_mapper;
    }
    public function get_node_type(): string
    {
        return Node\Intersection_Type::class;
    }
    /**
     * @param Node\IntersectionType $node
     */
    public function map_to_php_stan(Node $node): Intersection_Type
    {
        $types = [];
        foreach ($node->types as $intersectioned_type) {
            if ($intersectioned_type instanceof Fully_Qualified) {
                $types[] = $this->fully_qualified_node_mapper->map_to_php_stan($intersectioned_type);
                continue;
            }
            if ($intersectioned_type instanceof Name) {
                $types[] = $this->name_node_mapper->map_to_php_stan($intersectioned_type);
                continue;
            }
            $types[] = $this->identifier_node_mapper->map_to_php_stan($intersectioned_type);
        }
        return new Intersection_Type($types);
    }
}