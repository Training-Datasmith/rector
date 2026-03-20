<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Analyser\Name_Scope;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Intersection_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Intersection_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Rector\Static_Type_Mapper\Contract\Php_Doc_Parser\Php_Doc_Type_Mapper_Interface;
/**
 * @implements PhpDocTypeMapperInterface<IntersectionTypeNode>
 */
final class Intersection_Php_Doc_Type_Mapper implements Php_Doc_Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private \Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper;
    public function __construct(\Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper $identifier_php_doc_type_mapper)
    {
        $this->identifier_php_doc_type_mapper = $identifier_php_doc_type_mapper;
    }
    public function get_node_type(): string
    {
        return Intersection_Type_Node::class;
    }
    /**
     * @param IntersectionTypeNode $typeNode
     */
    public function map_to_php_stan_type(Type_Node $type_node, Node $node, Name_Scope $name_scope): Type
    {
        $intersectioned_types = [];
        foreach ($type_node->types as $intersectioned_type_node) {
            if (!$intersectioned_type_node instanceof Identifier_Type_Node) {
                return new Mixed_Type();
            }
            $intersectioned_types[] = $this->identifier_php_doc_type_mapper->map_identifier_type_node($intersectioned_type_node, $node);
        }
        return new Intersection_Type($intersectioned_types);
    }
}