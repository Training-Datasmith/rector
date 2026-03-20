<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Stan\Analyser\Scope;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
/**
 * @implements PhpParserNodeMapperInterface<Expr>
 */
final class Expr_Node_Mapper implements Php_Parser_Node_Mapper_Interface
{
    public function get_node_type(): string
    {
        return Expr::class;
    }
    /**
     * @param Expr $node
     */
    public function map_to_php_stan(Node $node): Type
    {
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return new Mixed_Type();
        }
        return $scope->get_type($node);
    }
}