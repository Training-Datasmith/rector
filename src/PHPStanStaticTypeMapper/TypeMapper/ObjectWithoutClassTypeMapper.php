<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Accessory\Has_Method_Type;
use Php_Stan\Type\Accessory\Has_Property_Type;
use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Php_Stan\Object_Without_Class_Type_With_Parent_Types;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @implements TypeMapperInterface<ObjectWithoutClassType>
 */
final class Object_Without_Class_Type_Mapper implements Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    public function __construct(Php_Version_Provider $php_version_provider)
    {
        $this->php_version_provider = $php_version_provider;
    }
    public function get_node_class(): string
    {
        return Object_Without_Class_Type::class;
    }
    /**
     * @param ObjectWithoutClassType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param ObjectWithoutClassType|HasMethodType|HasPropertyType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        // special case for anonymous classes that implement another type
        if ($type instanceof Object_Without_Class_Type_With_Parent_Types) {
            $parent_types = $type->get_parent_types();
            if (count($parent_types) === 1) {
                $parent_type = $parent_types[0];
                return new Fully_Qualified($parent_type->get_class_name());
            }
        }
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::OBJECT_TYPE)) {
            return null;
        }
        return new Identifier('object');
    }
}