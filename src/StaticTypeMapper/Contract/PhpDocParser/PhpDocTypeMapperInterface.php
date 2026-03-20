<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Contract\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Analyser\Name_Scope;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Type;
/**
 * @template TTypeNode as TypeNode
 */
interface Php_Doc_Type_Mapper_Interface
{
    /**
     * @return class-string<TTypeNode>
     */
    public function get_node_type(): string;
    /**
     * @param TTypeNode $typeNode
     */
    public function map_to_php_stan_type(Type_Node $type_node, Node $node, Name_Scope $name_scope): Type;
}