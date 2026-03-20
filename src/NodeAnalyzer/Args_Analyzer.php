<?php

declare (strict_types=1);
namespace Rector\Node_Analyzer;

use Php_Parser\Node\Arg;
use Php_Parser\Node\Identifier;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
final class Args_Analyzer
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
     * @param Arg[] $args
     */
    public function has_named_arg(array $args): bool
    {
        foreach ($args as $arg) {
            if ($arg->name instanceof Identifier) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param Arg[] $args
     */
    public function resolve_arg_position(array $args, string $name, int $default_position): int
    {
        foreach ($args as $position => $arg) {
            if (!$arg->name instanceof Identifier) {
                continue;
            }
            if (!$this->node_name_resolver->is_name($arg->name, $name)) {
                continue;
            }
            return $position;
        }
        return $default_position;
    }
    /**
     * @param Arg[] $args
     */
    public function resolve_first_named_arg_position(array $args): ?int
    {
        $position = 0;
        foreach ($args as $arg) {
            if ($arg->name instanceof Identifier) {
                return $position;
            }
            ++$position;
        }
        return null;
    }
}