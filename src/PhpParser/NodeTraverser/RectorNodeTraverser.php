<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Traverser;

use LogicException;
use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Stmt;
use Php_Parser\Node_Traverser_Interface;
use Php_Parser\Node_Visitor;
use Rector\Configuration\Configuration_Rule_Filter;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php_Parser\Node\Custom_Node\File_Without_Namespace;
use Rector\Php_Parser\Node\File_Node;
use Rector\Version_Bonding\Composer_Package_Constraint_Filter;
use Rector\Version_Bonding\Php_Versioned_Filter;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 *  Based on native NodeTraverser class, but heavily customized for Rector needs.
 *
 *  The main differences are:
 *  - no leaveNode(), as we do all in enterNode() that calls refactor() method
 *  - cached visitors per node class for performance, e.g. when we find rules for Class_ node, they're cached for next time
 *  - immutability features, register Rector rules once, then use; no changes on the fly
 *
 * @see \Rector\Tests\PhpParser\NodeTraverser\RectorNodeTraverserTest
 * @internal No BC promise on this class, it might change any time.
 */
final class Rector_Node_Traverser implements Node_Traverser_Interface
{
    /**
     * @var RectorInterface[]
     */
    private array $rectors;
    /**
     * @readonly
     */
    private Php_Versioned_Filter $php_versioned_filter;
    /**
     * @readonly
     */
    private Composer_Package_Constraint_Filter $composer_package_constraint_filter;
    /**
     * @readonly
     */
    private Configuration_Rule_Filter $configuration_rule_filter;
    /**
     * @var RectorInterface[]
     */
    private array $visitors = [];
    private bool $stop_traversal;
    private bool $are_node_visitors_prepared = \false;
    /**
     * @var array<class-string<Node>, RectorInterface[]>
     */
    private array $visitors_per_node_class = [];
    /**
     * @param RectorInterface[] $rectors
     */
    public function __construct(array $rectors, Php_Versioned_Filter $php_versioned_filter, Composer_Package_Constraint_Filter $composer_package_constraint_filter, Configuration_Rule_Filter $configuration_rule_filter)
    {
        $this->rectors = $rectors;
        $this->php_versioned_filter = $php_versioned_filter;
        $this->composer_package_constraint_filter = $composer_package_constraint_filter;
        $this->configuration_rule_filter = $configuration_rule_filter;
    }
    public function add_visitor(Node_Visitor $visitor): void
    {
        throw new Should_Not_Happen_Exception('The immutable node traverser does not support adding visitors.');
    }
    public function remove_visitor(Node_Visitor $visitor): void
    {
        throw new Should_Not_Happen_Exception('The immutable node traverser does not support removing visitors.');
    }
    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function traverse(array $nodes): array
    {
        $this->prepare_node_visitors();
        $this->stop_traversal = \false;
        foreach ($this->visitors as $visitor) {
            if (null !== $return = $visitor->before_traverse($nodes)) {
                $nodes = $return;
            }
        }
        $nodes = $this->traverse_array($nodes);
        for ($i = \count($this->visitors) - 1; $i >= 0; --$i) {
            $visitor = $this->visitors[$i];
            if (null !== $return = $visitor->after_traverse($nodes)) {
                $nodes = $return;
            }
        }
        return $nodes;
    }
    /**
     * @param RectorInterface[] $rectors
     * @api used in tests to update the active rules
     *
     * @internal Used only in Rector core, not supported outside. Might change any time.
     */
    public function refresh_php_rectors(array $rectors): void
    {
        Assert::all_is_instance_of($rectors, Rector_Interface::class);
        $this->rectors = $rectors;
        $this->visitors = [];
        $this->visitors_per_node_class = [];
        $this->are_node_visitors_prepared = \false;
        $this->prepare_node_visitors();
    }
    /**
     * @return RectorInterface[]
     *
     * @api used in tests
     */
    public function get_visitors_for_node(Node $node): array
    {
        $node_class = get_class($node);
        if (!isset($this->visitors_per_node_class[$node_class])) {
            $this->visitors_per_node_class[$node_class] = [];
            /** @var RectorInterface $visitor */
            foreach ($this->visitors as $visitor) {
                foreach ($visitor->get_node_types() as $node_type) {
                    // BC layer matching
                    if ($node_type === File_Without_Namespace::class && $node_class === File_Node::class) {
                        $this->visitors_per_node_class[$node_class][] = $visitor;
                        continue;
                    }
                    if (is_a($node_class, $node_type, \true)) {
                        $this->visitors_per_node_class[$node_class][] = $visitor;
                        continue 2;
                    }
                }
            }
        }
        return $this->visitors_per_node_class[$node_class];
    }
    private function traverse_node(Node $node): void
    {
        foreach ($node->get_sub_node_names() as $name) {
            $sub_node = $node->{$name};
            if (\is_array($sub_node)) {
                $node->{$name} = $this->traverse_array($sub_node);
                if ($this->stop_traversal) {
                    break;
                }
                continue;
            }
            if (!$sub_node instanceof Node) {
                continue;
            }
            $traverse_children = \true;
            $current_node_visitors = $this->get_visitors_for_node($sub_node);
            foreach ($current_node_visitors as $current_node_visitor) {
                $return = $current_node_visitor->enter_node($sub_node);
                if ($return !== null) {
                    if ($return instanceof Node) {
                        $original_sub_node_class = get_class($sub_node);
                        $this->ensure_replacement_reasonable($sub_node, $return);
                        $sub_node = $return;
                        $node->{$name} = $return;
                        if ($original_sub_node_class !== get_class($sub_node)) {
                            // stop traversing as node type changed and visitors won't work
                            continue 2;
                        }
                    } elseif ($return === Node_Visitor::DONT_TRAVERSE_CHILDREN) {
                        $traverse_children = \false;
                    } elseif ($return === Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN) {
                        $traverse_children = \false;
                        break;
                    } elseif ($return === Node_Visitor::STOP_TRAVERSAL) {
                        $this->stop_traversal = \true;
                        break 2;
                    } elseif ($return === Node_Visitor::REPLACE_WITH_NULL) {
                        $node->{$name} = null;
                        continue 2;
                    } else {
                        throw new LogicException('enterNode() returned invalid value of type ' . gettype($return));
                    }
                }
            }
            if ($traverse_children) {
                $this->traverse_node($sub_node);
                if ($this->stop_traversal) {
                    break;
                }
            }
        }
    }
    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    private function traverse_array(array $nodes): array
    {
        $do_nodes = [];
        foreach ($nodes as $i => $node) {
            if (!$node instanceof Node) {
                if (\is_array($node)) {
                    throw new LogicException('Invalid node structure: Contains nested arrays');
                }
                continue;
            }
            $traverse_children = \true;
            $current_node_visitors = $this->get_visitors_for_node($node);
            foreach ($current_node_visitors as $current_node_visitor) {
                $return = $current_node_visitor->enter_node($node);
                if ($return !== null) {
                    if ($return instanceof Node) {
                        $original_node_node_class = get_class($node);
                        $this->ensure_replacement_reasonable($node, $return);
                        $nodes[$i] = $node = $return;
                        if ($original_node_node_class !== get_class($return)) {
                            // stop traversing as node type changed and visitors won't work
                            continue 2;
                        }
                    } elseif (\is_array($return)) {
                        $do_nodes[] = [$i, $return];
                        continue 2;
                    } elseif ($return === Node_Visitor::REMOVE_NODE) {
                        $do_nodes[] = [$i, []];
                        continue 2;
                    } elseif ($return === Node_Visitor::DONT_TRAVERSE_CHILDREN) {
                        $traverse_children = \false;
                    } elseif ($return === Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN) {
                        $traverse_children = \false;
                        break;
                    } elseif ($return === Node_Visitor::STOP_TRAVERSAL) {
                        $this->stop_traversal = \true;
                        break 2;
                    } elseif ($return === Node_Visitor::REPLACE_WITH_NULL) {
                        throw new LogicException('REPLACE_WITH_NULL can not be used if the parent structure is an array');
                    } else {
                        throw new LogicException('enterNode() returned invalid value of type ' . gettype($return));
                    }
                }
            }
            if ($traverse_children) {
                $this->traverse_node($node);
                if ($this->stop_traversal) {
                    break;
                }
            }
        }
        if ($do_nodes !== []) {
            while ([$i, $replace] = array_pop($do_nodes)) {
                array_splice($nodes, $i, 1, $replace);
            }
        }
        return $nodes;
    }
    private function ensure_replacement_reasonable(Node $old, Node $new): void
    {
        if ($old instanceof Stmt) {
            if ($new instanceof Expr) {
                throw new LogicException(sprintf('Trying to replace statement (%s) ', $old->get_type()) . sprintf('with expression (%s). Are you missing a ', $new->get_type()) . 'Stmt_Expression wrapper?');
            }
            return;
        }
        if ($new instanceof Stmt) {
            throw new LogicException(sprintf('Trying to replace expression (%s) ', $old->get_type()) . sprintf('with statement (%s)', $new->get_type()));
        }
    }
    /**
     * This must happen after $this->configuration is set after ProcessCommand::execute() is run, otherwise we get default false positives.
     *
     * This should be removed after https://github.com/rectorphp/rector/issues/5584 is resolved
     */
    private function prepare_node_visitors(): void
    {
        if ($this->are_node_visitors_prepared) {
            return;
        }
        // filter out by PHP version
        $this->visitors = $this->php_versioned_filter->filter($this->rectors);
        // filter out by composer package constraint
        $this->visitors = $this->composer_package_constraint_filter->filter($this->visitors);
        // filter by configuration
        $this->visitors = $this->configuration_rule_filter->filter($this->visitors);
        $this->are_node_visitors_prepared = \true;
    }
}