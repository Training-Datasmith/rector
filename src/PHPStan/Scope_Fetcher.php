<?php

declare (strict_types=1);
namespace Rector\Php_Stan;

use Php_Parser\Node;
use Php_Stan\Analyser\Mutating_Scope;
use Php_Stan\Analyser\Scope;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Scope_Fetcher
{
    public static function fetch(Node $node): Scope
    {
        /** @var MutatingScope|null $currentScope */
        $current_scope = $node->get_attribute(Attribute_Key::SCOPE);
        if (!$current_scope instanceof Scope) {
            $error_message = sprintf('Scope not available on "%s" node. Fix scope refresh on changed nodes first', get_class($node));
            throw new Should_Not_Happen_Exception($error_message);
        }
        return $current_scope;
    }
}