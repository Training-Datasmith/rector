<?php

declare (strict_types=1);
namespace Rector\Process_Analyzer;

use Php_Parser\Node;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Node_Analyzer\Scope_Analyzer;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * This service verify if the Node:
 *
 *      - already applied same Rector rule before current Rector rule on last previous Rector rule.
 *      - just re-printed but token start still >= 0
 */
final class Rectified_Analyzer
{
    /**
     * @readonly
     */
    private Scope_Analyzer $scope_analyzer;
    public function __construct(Scope_Analyzer $scope_analyzer)
    {
        $this->scope_analyzer = $scope_analyzer;
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    public function has_rectified(string $rector_class, Node $node): bool
    {
        $original_node = $node->get_attribute(Attribute_Key::ORIGINAL_NODE);
        if ($this->has_consecutive_created_by_rule($rector_class, $node, $original_node)) {
            return \true;
        }
        return $this->is_just_reprinted_overlapped_token_start($node, $original_node);
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    private function has_consecutive_created_by_rule(string $rector_class, Node $node, ?Node $original_node): bool
    {
        $created_by_rule_node = $original_node ?? $node;
        /** @var class-string<RectorInterface>[] $createdByRule */
        $created_by_rule = $created_by_rule_node->get_attribute(Attribute_Key::CREATED_BY_RULE) ?? [];
        if ($created_by_rule === []) {
            return \false;
        }
        return end($created_by_rule) === $rector_class;
    }
    private function is_just_reprinted_overlapped_token_start(Node $node, ?Node $original_node): bool
    {
        if ($original_node instanceof Node) {
            return \false;
        }
        /**
         * Start token pos must be < 0 to continue, as the node and parent node just re-printed
         *
         * - Node's original node is null
         * - Parent Node's original node is null
         */
        $start_token_pos = $node->get_start_token_pos();
        if ($start_token_pos >= 0) {
            return \true;
        }
        if (!$this->scope_analyzer->is_refreshable($node)) {
            return \false;
        }
        return !in_array(Attribute_Key::SCOPE, array_keys($node->get_attributes()), \true);
    }
}