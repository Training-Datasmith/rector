<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Type_Analyzer;

use Php_Parser\Node\Expr;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
final class String_Type_Analyzer
{
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    public function __construct(Node_Type_Resolver $node_type_resolver)
    {
        $this->node_type_resolver = $node_type_resolver;
    }
    public function is_string_or_union_string_only_type(Expr $expr): bool
    {
        $node_type = $this->node_type_resolver->get_type($expr);
        return $node_type->is_string()->yes();
    }
}