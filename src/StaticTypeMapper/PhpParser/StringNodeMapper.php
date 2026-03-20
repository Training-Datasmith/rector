<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Type\String_Type;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
/**
 * @implements PhpParserNodeMapperInterface<String_>
 */
final class String_Node_Mapper implements Php_Parser_Node_Mapper_Interface
{
    public function get_node_type(): string
    {
        return String_::class;
    }
    /**
     * @param String_ $node
     */
    public function map_to_php_stan(Node $node): String_Type
    {
        return new String_Type();
    }
}