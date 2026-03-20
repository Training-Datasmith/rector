<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node\Name;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Static_Type;
use Php_Stan\Type\Type;
use Rector\Enum\Object_Reference;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Static_Type_Mapper\Value_Object\Type\Self_Static_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Simple_Static_Type;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @see \Rector\Tests\NodeTypeResolver\StaticTypeMapper\StaticTypeMapperTest
 *
 * @implements TypeMapperInterface<StaticType>
 */
final class Static_Type_Mapper implements Type_Mapper_Interface
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
        return Static_Type::class;
    }
    /**
     * @param StaticType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param SimpleStaticType|StaticType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): Name
    {
        if ($type instanceof Self_Static_Type) {
            return new Name(Object_Reference::SELF);
        }
        if ($type_kind !== Type_Kind::RETURN) {
            return new Name(Object_Reference::SELF);
        }
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::STATIC_RETURN_TYPE)) {
            return new Name(Object_Reference::SELF);
        }
        return new Name(Object_Reference::STATIC);
    }
}