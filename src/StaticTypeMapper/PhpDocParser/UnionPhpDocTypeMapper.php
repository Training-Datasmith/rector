<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Analyser\Name_Scope;
use Php_Stan\Php_Doc\Type_Node_Resolver;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Intersection_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Union_Type_Node;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Php_Stan\Type\Type_Factory;
use Rector\Static_Type_Mapper\Contract\Php_Doc_Parser\Php_Doc_Type_Mapper_Interface;
/**
 * @implements PhpDocTypeMapperInterface<UnionTypeNode>
 */
final class Union_Php_Doc_Type_Mapper implements Php_Doc_Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private Type_Factory $type_factory;
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper;
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Doc_Parser\Intersection_Php_Doc_Type_Mapper $intersection_php_doc_type_mapper;
    /**
     * @readonly
     */
    private Type_Node_Resolver $type_node_resolver;
    public function __construct(Type_Factory $type_factory, \Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper, \Rector\Static_Type_Mapper\Php_Doc_Parser\Intersection_Php_Doc_Type_Mapper $intersection_php_doc_type_mapper, Type_Node_Resolver $type_node_resolver)
    {
        $this->type_factory = $type_factory;
        $this->identifier_php_doc_type_mapper = $identifier_php_doc_type_mapper;
        $this->intersection_php_doc_type_mapper = $intersection_php_doc_type_mapper;
        $this->type_node_resolver = $type_node_resolver;
    }
    public function get_node_type(): string
    {
        return Union_Type_Node::class;
    }
    /**
     * @param UnionTypeNode $typeNode
     */
    public function map_to_php_stan_type(Type_Node $type_node, Node $node, Name_Scope $name_scope): Type
    {
        $unioned_types = [];
        foreach ($type_node->types as $unioned_type_node) {
            if ($unioned_type_node instanceof Identifier_Type_Node) {
                $unioned_types[] = $this->identifier_php_doc_type_mapper->map_to_php_stan_type($unioned_type_node, $node, $name_scope);
                continue;
            }
            if ($unioned_type_node instanceof Intersection_Type_Node) {
                $unioned_types[] = $this->intersection_php_doc_type_mapper->map_to_php_stan_type($unioned_type_node, $node, $name_scope);
                continue;
            }
            $unioned_types[] = $this->type_node_resolver->resolve($unioned_type_node, $name_scope);
        }
        // to prevent missing class error, e.g. in tests
        return $this->type_factory->create_mixed_passed_or_union_type_and_keep_constant($unioned_types);
    }
}