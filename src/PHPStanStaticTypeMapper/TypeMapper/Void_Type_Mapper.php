<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Type;
use Php_Stan\Type\Void_Type;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @implements TypeMapperInterface<VoidType>
 */
final class Void_Type_Mapper implements Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @var string
     */
    private const VOID = 'void';
    public function __construct(Php_Version_Provider $php_version_provider)
    {
        $this->php_version_provider = $php_version_provider;
    }
    public function get_node_class(): string
    {
        return Void_Type::class;
    }
    /**
     * @param VoidType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param TypeKind::* $typeKind
     * @param VoidType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::VOID_TYPE)) {
            return null;
        }
        if (in_array($type_kind, [Type_Kind::PARAM, Type_Kind::PROPERTY, Type_Kind::UNION], \true)) {
            return null;
        }
        return new Identifier(self::VOID);
    }
}