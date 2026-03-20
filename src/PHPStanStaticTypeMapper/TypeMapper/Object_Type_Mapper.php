<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Generic\Generic_Object_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Traverser;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Non_Existing_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Self_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @implements TypeMapperInterface<ObjectType>
 */
final class Object_Type_Mapper implements Type_Mapper_Interface
{
    public function get_node_class(): string
    {
        return Object_Type::class;
    }
    /**
     * @param ObjectType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        $type = Type_Traverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof Array_Type && ($type->get_item_type() instanceof Mixed_Type && $type->get_key_type() instanceof Mixed_Type)) {
                return new Array_Type(new Mixed_Type(), new Mixed_Type(\true));
            }
            if (!$type instanceof Object_Type) {
                return $traverse($type);
            }
            $type_class = get_class($type);
            // early native ObjectType check
            if ($type_class === \Php_Stan\Type\Object_Type::class) {
                return new Object_Type('\\' . $type->get_class_name());
            }
            if ($type instanceof Fully_Qualified_Object_Type) {
                return new Object_Type('\\' . $type->get_class_name());
            }
            if ($type instanceof Generic_Object_Type) {
                return $traverse(new Generic_Object_Type('\\' . $type->get_class_name(), $type->get_types()), $traverse);
            }
            return $traverse($type, $traverse);
        });
        return $type->to_php_doc_node();
    }
    /**
     * @param ObjectType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        if ($type instanceof Self_Object_Type) {
            return new Name('self');
        }
        if ($type instanceof Shortened_Object_Type || $type instanceof Aliased_Object_Type) {
            return new Fully_Qualified($type->get_fully_qualified_name());
        }
        if ($type instanceof Fully_Qualified_Object_Type) {
            $class_name = $type->get_class_name();
            if (strncmp($class_name, '\\', strlen('\\')) === 0) {
                // skip leading \
                return new Fully_Qualified(Strings::substring($class_name, 1));
            }
            return new Fully_Qualified($class_name);
        }
        if ($type instanceof Non_Existing_Object_Type) {
            return null;
        }
        return new Fully_Qualified($type->get_class_name());
    }
}