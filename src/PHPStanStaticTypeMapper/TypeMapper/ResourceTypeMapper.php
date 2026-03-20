<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Resource_Type;
use Php_Stan\Type\Type;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
/**
 * @implements TypeMapperInterface<ResourceType>
 */
final class Resource_Type_Mapper implements Type_Mapper_Interface
{
    public function get_node_class(): string
    {
        return Resource_Type::class;
    }
    /**
     * @param ResourceType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param ResourceType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        return null;
    }
}