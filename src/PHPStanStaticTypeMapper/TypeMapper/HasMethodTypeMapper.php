<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Accessory\Has_Method_Type;
use Php_Stan\Type\Type;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
/**
 * @implements TypeMapperInterface<HasMethodType>
 */
final class Has_Method_Type_Mapper implements Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private \Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Without_Class_Type_Mapper $object_without_class_type_mapper;
    public function __construct(\Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Without_Class_Type_Mapper $object_without_class_type_mapper)
    {
        $this->object_without_class_type_mapper = $object_without_class_type_mapper;
    }
    public function get_node_class(): string
    {
        return Has_Method_Type::class;
    }
    /**
     * @param HasMethodType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param HasMethodType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        return $this->object_without_class_type_mapper->map_to_php_parser_node($type, $type_kind);
    }
}