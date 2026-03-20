<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Complex_Type;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Param_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Return_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Template_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Throws_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Var_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Rector\Exception\Not_Implemented_Yet_Exception;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Php_Stan_Static_Type_Mapper\Php_Stan_Static_Type_Mapper;
use Rector\Static_Type_Mapper\Mapper\Php_Parser_Node_Mapper;
use Rector\Static_Type_Mapper\Naming\Name_Scope_Factory;
use Rector\Static_Type_Mapper\Php_Doc\Php_Doc_Type_Mapper;
/**
 * Maps PhpParser <=> PHPStan <=> PHPStan doc <=> string type nodes between all possible formats
 * @see \Rector\Tests\NodeTypeResolver\StaticTypeMapper\StaticTypeMapperTest
 */
final class Static_Type_Mapper
{
    /**
     * @readonly
     */
    private Name_Scope_Factory $name_scope_factory;
    /**
     * @readonly
     */
    private Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper;
    /**
     * @readonly
     */
    private Php_Doc_Type_Mapper $php_doc_type_mapper;
    /**
     * @readonly
     */
    private Php_Parser_Node_Mapper $php_parser_node_mapper;
    public function __construct(Name_Scope_Factory $name_scope_factory, Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper, Php_Doc_Type_Mapper $php_doc_type_mapper, Php_Parser_Node_Mapper $php_parser_node_mapper)
    {
        $this->name_scope_factory = $name_scope_factory;
        $this->php_stan_static_type_mapper = $php_stan_static_type_mapper;
        $this->php_doc_type_mapper = $php_doc_type_mapper;
        $this->php_parser_node_mapper = $php_parser_node_mapper;
    }
    public function map_php_stan_type_to_php_stan_php_doc_type_node(Type $php_stan_type): Type_Node
    {
        return $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($php_stan_type);
    }
    /**
     * @param TypeKind::* $typeKind
     * @return Name|ComplexType|Identifier|null
     */
    public function map_php_stan_type_to_php_parser_node(Type $php_stan_type, string $type_kind): ?Node
    {
        return $this->php_stan_static_type_mapper->map_to_php_parser_node($php_stan_type, $type_kind);
    }
    public function map_php_parser_node_php_stan_type(Node $node): Type
    {
        return $this->php_parser_node_mapper->map_to_php_stan_type($node);
    }
    public function map_php_stan_php_doc_type_to_php_stan_type(Php_Doc_Tag_Value_Node $php_doc_tag_value_node, Node $node): Type
    {
        if ($php_doc_tag_value_node instanceof Template_Tag_Value_Node) {
            // special case
            if (!$php_doc_tag_value_node->bound instanceof Type_Node) {
                return new Mixed_Type();
            }
            $name_scope = $this->name_scope_factory->create_name_scope_from_node_without_template_types($node);
            return $this->php_doc_type_mapper->map_to_php_stan_type($php_doc_tag_value_node->bound, $node, $name_scope);
        }
        if ($php_doc_tag_value_node instanceof Return_Tag_Value_Node || $php_doc_tag_value_node instanceof Param_Tag_Value_Node || $php_doc_tag_value_node instanceof Var_Tag_Value_Node || $php_doc_tag_value_node instanceof Throws_Tag_Value_Node) {
            return $this->map_php_stan_php_doc_type_node_to_php_stan_type($php_doc_tag_value_node->type, $node);
        }
        throw new Not_Implemented_Yet_Exception(__METHOD__ . ' for ' . get_class($php_doc_tag_value_node));
    }
    public function map_php_stan_php_doc_type_node_to_php_stan_type(Type_Node $type_node, Node $node): Type
    {
        $name_scope = $this->name_scope_factory->create_name_scope_from_node_without_template_types($node);
        return $this->php_doc_type_mapper->map_to_php_stan_type($type_node, $node, $name_scope);
    }
}