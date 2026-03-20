<?php

declare (strict_types=1);
namespace Rector\Node_Decorator;

use Php_Parser\Node;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Created_By_Rule_Decorator
{
    /**
     * @param array<Node>|Node $node
     * @param class-string<RectorInterface> $rectorClass
     */
    public function decorate($node, Node $original_node, string $rector_class): void
    {
        if ($node instanceof Node && $node === $original_node) {
            $this->create_by_rule($node, $rector_class);
            return;
        }
        if ($node instanceof Node) {
            $node = [$node];
        }
        foreach ($node as $single_node) {
            if (get_class($single_node) === get_class($original_node)) {
                $this->create_by_rule($single_node, $rector_class);
            }
        }
        $this->create_by_rule($original_node, $rector_class);
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    private function create_by_rule(Node $node, string $rector_class): void
    {
        /** @var class-string<RectorInterface>[] $createdByRule */
        $created_by_rule = $node->get_attribute(Attribute_Key::CREATED_BY_RULE) ?? [];
        // empty array, insert
        if ($created_by_rule === []) {
            $node->set_attribute(Attribute_Key::CREATED_BY_RULE, [$rector_class]);
            return;
        }
        // consecutive, no need to refill
        if (end($created_by_rule) === $rector_class) {
            return;
        }
        // filter out when exists, then append
        $created_by_rule = array_filter($created_by_rule, static fn(string $rector_rule): bool => $rector_rule !== $rector_class);
        $node->set_attribute(Attribute_Key::CREATED_BY_RULE, array_merge($created_by_rule, [$rector_class]));
    }
}