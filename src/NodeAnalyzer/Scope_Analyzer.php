<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node;
use Php_Parser\Node\Complex_Type;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
final class Scope_Analyzer
{
    /**
     * @var array<class-string<Node>>
     */
    private const NON_REFRESHABLE_NODES = [Name::class, Identifier::class, Complex_Type::class];
    public function is_refreshable(Node $node): bool
    {
        foreach (self::NON_REFRESHABLE_NODES as $no_scope_node) {
            if ($node instanceof $no_scope_node) {
                return \false;
            }
        }
        return \true;
    }
}