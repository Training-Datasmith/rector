<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Function_Like;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
final class Function_Like_Manipulator
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver)
    {
        $this->node_name_resolver = $node_name_resolver;
    }
    /**
     * @return string[]
     */
    public function resolve_param_names(Function_Like $function_like): array
    {
        $param_names = [];
        foreach ($function_like->get_params() as $param) {
            $param_names[] = $this->node_name_resolver->get_name($param);
        }
        return $param_names;
    }
}