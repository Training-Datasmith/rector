<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Contract;

use Rector\Node_Type_Resolver\Node_Type_Resolver;
interface Node_Type_Resolver_Aware_Interface
{
    public function autowire(Node_Type_Resolver $node_type_resolver): void;
}