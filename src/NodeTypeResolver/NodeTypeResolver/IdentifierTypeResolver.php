<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Float_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Iterable_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Object_Without_Class_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
/**
 * @implements NodeTypeResolverInterface<Identifier>
 */
final class Identifier_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Identifier::class];
    }
    /**
     * @param Identifier $node
     * @return StringType|BooleanType|ConstantBooleanType|NullType|ObjectWithoutClassType|ArrayType|IterableType|IntegerType|FloatType|MixedType
     */
    public function resolve(Node $node): Type
    {
        $lower_string = $node->to_lower_string();
        if ($lower_string === 'string') {
            return new String_Type();
        }
        if ($lower_string === 'bool') {
            return new Boolean_Type();
        }
        if ($lower_string === 'false') {
            return new Constant_Boolean_Type(\false);
        }
        if ($lower_string === 'true') {
            return new Constant_Boolean_Type(\true);
        }
        if ($lower_string === 'null') {
            return new Null_Type();
        }
        if ($lower_string === 'object') {
            return new Object_Without_Class_Type();
        }
        if ($lower_string === 'array') {
            return new Array_Type(new Mixed_Type(), new Mixed_Type());
        }
        if ($lower_string === 'int') {
            return new Integer_Type();
        }
        if ($lower_string === 'iterable') {
            return new Iterable_Type(new Mixed_Type(), new Mixed_Type());
        }
        if ($lower_string === 'float') {
            return new Float_Type();
        }
        return new Mixed_Type();
    }
}