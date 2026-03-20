<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node\Name;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Type;
use Rector\Enum\Object_Reference;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Static_Type_Mapper\Value_Object\Type\Parent_Static_Type;
/**
 * @implements TypeMapperInterface<ParentStaticType>
 */
final class Parent_Static_Type_Mapper implements Type_Mapper_Interface
{
    public function get_node_class(): string
    {
        return Parent_Static_Type::class;
    }
    /**
     * @param ParentStaticType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param ParentStaticType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): Name
    {
        return new Name(Object_Reference::PARENT);
    }
}