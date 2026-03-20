<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Mapper;

use Php_Parser\Node;
use Php_Stan\Type\Type;
use Rector\Exception\Not_Implemented_Yet_Exception;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
final class Php_Parser_Node_Mapper
{
    /**
     * @var PhpParserNodeMapperInterface[]
     * @readonly
     */
    private iterable $php_parser_node_mappers;
    /**
     * @param PhpParserNodeMapperInterface[] $phpParserNodeMappers
     */
    public function __construct(iterable $php_parser_node_mappers)
    {
        $this->php_parser_node_mappers = $php_parser_node_mappers;
    }
    public function map_to_php_stan_type(Node $node): Type
    {
        foreach ($this->php_parser_node_mappers as $php_parser_node_mapper) {
            if (!is_a($node, $php_parser_node_mapper->get_node_type())) {
                continue;
            }
            return $php_parser_node_mapper->map_to_php_stan($node);
        }
        throw new Not_Implemented_Yet_Exception(get_class($node));
    }
}