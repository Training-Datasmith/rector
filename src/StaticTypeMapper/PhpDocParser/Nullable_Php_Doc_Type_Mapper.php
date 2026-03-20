<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Analyser\Name_Scope;
use Php_Stan\Php_Doc\Type_Node_Resolver;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Nullable_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Static_Type_Mapper\Contract\Php_Doc_Parser\Php_Doc_Type_Mapper_Interface;
/**
 * @implements PhpDocTypeMapperInterface<NullableTypeNode>
 */
final class Nullable_Php_Doc_Type_Mapper implements Php_Doc_Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper;
    /**
     * @readonly
     */
    private Type_Node_Resolver $type_node_resolver;
    public function __construct(\Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper, Type_Node_Resolver $type_node_resolver)
    {
        $this->identifier_php_doc_type_mapper = $identifier_php_doc_type_mapper;
        $this->type_node_resolver = $type_node_resolver;
    }
    public function get_node_type(): string
    {
        return Nullable_Type_Node::class;
    }
    /**
     * @param NullableTypeNode $typeNode
     */
    public function map_to_php_stan_type(Type_Node $type_node, Node $node, Name_Scope $name_scope): Type
    {
        if ($type_node->type instanceof Identifier_Type_Node) {
            $type = $this->identifier_php_doc_type_mapper->map_to_php_stan_type($type_node->type, $node, $name_scope);
            if ($type instanceof Union_Type) {
                return new Union_Type(array_merge([new Null_Type()], $type->get_types()));
            }
            return new Union_Type([new Null_Type(), $type]);
        }
        // fallback to PHPStan resolver
        return $this->type_node_resolver->resolve($type_node, $name_scope);
    }
}