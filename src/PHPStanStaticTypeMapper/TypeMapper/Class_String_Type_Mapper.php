<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Class_String_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Traverser;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @implements TypeMapperInterface<ClassStringType>
 */
final class Class_String_Type_Mapper implements Type_Mapper_Interface
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
        return Class_String_Type::class;
    }
    /**
     * @param ClassStringType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        $type = Type_Traverser::map($type, static function (Type $type, callable $traverse): Type {
            if (!$type instanceof Object_Type) {
                return $traverse($type);
            }
            $type_class = get_class($type);
            if ($type_class === \Php_Stan\Type\Object_Type::class) {
                return new Object_Type('\\' . $type->get_class_name());
            }
            return $traverse($type);
        });
        return $type->to_php_doc_node();
    }
    /**
     * @param ClassStringType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::SCALAR_TYPES)) {
            return null;
        }
        return new Identifier('string');
    }
}