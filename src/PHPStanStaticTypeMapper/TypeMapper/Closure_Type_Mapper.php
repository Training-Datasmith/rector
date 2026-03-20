<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Stan\Php_Doc_Parser\Ast\Node as AstNode;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Closure_Type;
use Php_Stan\Type\Type;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Fully_Qualified_Identifier_Type_Node;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Value_Object\Php_Version_Feature;
/**
 * @implements TypeMapperInterface<ClosureType>
 */
final class Closure_Type_Mapper implements Type_Mapper_Interface
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
        return Closure_Type::class;
    }
    /**
     * @param ClosureType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        $type_node = $type->to_php_doc_node();
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $php_doc_node_traverser->traverse_with_callable($type_node, '', static function (Ast_Node $ast_node): ?Fully_Qualified_Identifier_Type_Node {
            if (!$ast_node instanceof Identifier_Type_Node) {
                return null;
            }
            if ($ast_node->name !== 'Closure') {
                return null;
            }
            return new Fully_Qualified_Identifier_Type_Node('Closure');
        });
        return $type_node;
    }
    /**
     * @param TypeKind::* $typeKind
     * @param ClosureType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        // ref https://3v4l.org/iKMK6#v5.3.29
        if ($type_kind === Type_Kind::PARAM && $this->php_version_provider->is_at_least_php_version(Php_Version_Feature::ANONYMOUS_FUNCTION_PARAM_TYPE)) {
            return new Fully_Qualified('Closure');
        }
        // ref https://3v4l.org/g8WvW#v7.4.0
        if ($type_kind === Type_Kind::PROPERTY && $this->php_version_provider->is_at_least_php_version(Php_Version_Feature::TYPED_PROPERTIES)) {
            return new Fully_Qualified('Closure');
        }
        // ref https://3v4l.org/nUreN#v7.0.0
        if ($type_kind === Type_Kind::RETURN && $this->php_version_provider->is_at_least_php_version(Php_Version_Feature::ANONYMOUS_FUNCTION_RETURN_TYPE)) {
            return new Fully_Qualified('Closure');
        }
        // ref https://3v4l.org/ruh5g#v8.0.0
        if ($type_kind === Type_Kind::UNION && $this->php_version_provider->is_at_least_php_version(Php_Version_Feature::UNION_TYPES)) {
            return new Fully_Qualified('Closure');
        }
        return null;
    }
}