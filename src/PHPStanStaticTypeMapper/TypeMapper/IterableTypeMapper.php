<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node\Identifier;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Iterable_Type;
use Php_Stan\Type\Type;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
/**
 * @implements TypeMapperInterface<IterableType>
 */
final class Iterable_Type_Mapper implements Type_Mapper_Interface
{
    public function get_node_class(): string
    {
        return Iterable_Type::class;
    }
    /**
     * @param IterableType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param IterableType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): Identifier
    {
        return new Identifier('iterable');
    }
}