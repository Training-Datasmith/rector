<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Conditional_Type_For_Parameter;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Php_Stan_Static_Type_Mapper\Php_Stan_Static_Type_Mapper;
/**
 * @implements TypeMapperInterface<ConditionalTypeForParameter>
 */
final class Conditional_Type_For_Parameter_Mapper implements Type_Mapper_Interface
{
    private Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper;
    public function autowire(Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper): void
    {
        $this->php_stan_static_type_mapper = $php_stan_static_type_mapper;
    }
    public function get_node_class(): string
    {
        return Conditional_Type_For_Parameter::class;
    }
    /**
     * @param ConditionalTypeForParameter $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        return $type->to_php_doc_node();
    }
    /**
     * @param ConditionalTypeForParameter $type
     * @param TypeKind::* $typeKind
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        $type = Type_Combinator::union($type->get_if(), $type->get_else());
        return $this->php_stan_static_type_mapper->map_to_php_parser_node($type, $type_kind);
    }
}