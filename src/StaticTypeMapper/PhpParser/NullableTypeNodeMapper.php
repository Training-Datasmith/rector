<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Nullable_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Php_Stan\Type\Type_Factory;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
/**
 * @implements PhpParserNodeMapperInterface<NullableType>
 */
final class Nullable_Type_Node_Mapper implements Php_Parser_Node_Mapper_Interface
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
    public function __construct(Type_Factory $type_factory, \Rector\Static_Type_Mapper\Php_Parser\Fully_Qualified_Node_Mapper $fully_qualified_node_mapper, \Rector\Static_Type_Mapper\Php_Parser\Name_Node_Mapper $name_node_mapper, \Rector\Static_Type_Mapper\Php_Parser\Identifier_Node_Mapper $identifier_node_mapper)
    {
        $this->type_factory = $type_factory;
        $this->fully_qualified_node_mapper = $fully_qualified_node_mapper;
        $this->name_node_mapper = $name_node_mapper;
        $this->identifier_node_mapper = $identifier_node_mapper;
    }
    public function get_node_type(): string
    {
        return Nullable_Type::class;
    }
    /**
     * @param NullableType $node
     */
    public function map_to_php_stan(Node $node): Type
    {
        if ($node->type instanceof Fully_Qualified) {
            $type = $this->fully_qualified_node_mapper->map_to_php_stan($node->type);
        } elseif ($node->type instanceof Name) {
            $type = $this->name_node_mapper->map_to_php_stan($node->type);
        } else {
            $type = $this->identifier_node_mapper->map_to_php_stan($node->type);
        }
        $types = [$type, new Null_Type()];
        return $this->type_factory->create_mixed_passed_or_union_type($types);
    }
}