<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
/**
 * @implements PhpParserNodeMapperInterface<FullyQualified>
 */
final class Fully_Qualified_Node_Mapper implements Php_Parser_Node_Mapper_Interface
{
    public function get_node_type(): string
    {
        return Fully_Qualified::class;
    }
    /**
     * @param FullyQualified $node
     */
    public function map_to_php_stan(Node $node): Type
    {
        $original_name = (string) $node->get_attribute(Attribute_Key::ORIGINAL_NAME);
        $fully_qualified_name = $node->to_string();
        // is aliased?
        if ($this->is_aliased_name($original_name, $fully_qualified_name) && $original_name !== $fully_qualified_name) {
            return new Aliased_Object_Type($original_name, $fully_qualified_name);
        }
        return new Fully_Qualified_Object_Type($fully_qualified_name);
    }
    private function is_aliased_name(string $original_name, string $fully_qualified_name): bool
    {
        if ($original_name === '') {
            return \false;
        }
        if ($original_name === $fully_qualified_name) {
            return \false;
        }
        return substr_compare($fully_qualified_name, '\\' . $original_name, -strlen('\\' . $original_name)) !== 0;
    }
}