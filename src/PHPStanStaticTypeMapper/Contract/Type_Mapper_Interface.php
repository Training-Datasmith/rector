<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Contract;

use Php_Parser\Node;
use Php_Parser\Node\Complex_Type;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Type;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
/**
 * @template TType of Type
 */
interface Type_Mapper_Interface
{
    /**
     * @return class-string<TType>
     */
    public function get_node_class(): string;
    /**
     * @param TType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node;
    /**
     * @param TType $type
     * @param TypeKind::* $typeKind
     * @return Name|ComplexType|Identifier|null
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node;
}