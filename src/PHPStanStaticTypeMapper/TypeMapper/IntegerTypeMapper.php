<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Type;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @implements TypeMapperInterface<IntegerType>
 */
final class Integer_Type_Mapper implements Type_Mapper_Interface
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
        return Integer_Type::class;
    }
    /**
     * @param IntegerType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Identifier_Type_Node
    {
        // note: cannot be handled by PHPStan as uses explicit values
        return new Identifier_Type_Node('int');
    }
    /**
     * @param IntegerType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::SCALAR_TYPES)) {
            return null;
        }
        return new Identifier('int');
    }
}