<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Stan\Type\Type;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
use Rector\Static_Type_Mapper\Mapper\Scalar_String_To_Type_Mapper;
/**
 * @implements PhpParserNodeMapperInterface<Identifier>
 */
final class Identifier_Node_Mapper implements Php_Parser_Node_Mapper_Interface
{
    /**
     * @readonly
     */
    private Scalar_String_To_Type_Mapper $scalar_string_to_type_mapper;
    public function __construct(Scalar_String_To_Type_Mapper $scalar_string_to_type_mapper)
    {
        $this->scalar_string_to_type_mapper = $scalar_string_to_type_mapper;
    }
    public function get_node_type(): string
    {
        return Identifier::class;
    }
    /**
     * @param Identifier $node
     */
    public function map_to_php_stan(Node $node): Type
    {
        return $this->scalar_string_to_type_mapper->map_scalar_string_to_type($node->name);
    }
}