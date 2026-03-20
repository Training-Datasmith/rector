<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Php_Doc_Parser;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Contract\Php_Doc_Node_Visitor_Interface;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Exception\Invalid_Traverse_Exception;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Callable_Php_Doc_Node_Visitor;
/**
 * @api
 *
 * Mimics
 * https://github.com/nikic/PHP-Parser/blob/4abdcde5f16269959a834e4e58ea0ba0938ab133/lib/PhpParser/NodeTraverser.php
 *
 * @see \Rector\Tests\PhpDocParser\PhpDocParser\SimplePhpDocNodeTraverser\PhpDocNodeTraverserTest
 */
final class Php_Doc_Node_Traverser
{
    /**
     * If NodeVisitor::enterNode() returns DONT_TRAVERSE_CHILDREN, child nodes of the current node will not be traversed
     * for any visitors.
     *
     * For subsequent visitors enterNode() will still be called on the current node and leaveNode() will also be invoked
     * for the current node.
     *
     * @api
     * @var int
     */
    public const DONT_TRAVERSE_CHILDREN = 1;
    /**
     * If NodeVisitor::enterNode() or NodeVisitor::leaveNode() returns STOP_TRAVERSAL, traversal is aborted.
     *
     * The afterTraverse() method will still be invoked.
     *
     * @api
     * @var int
     */
    public const STOP_TRAVERSAL = 2;
    /**
     * If NodeVisitor::leaveNode() returns NODE_REMOVE for a node that occurs in an array, it will be removed from the
     * array.
     *
     * For subsequent visitors leaveNode() will still be invoked for the removed node.
     *
     * @api
     * @var int
     */
    public const NODE_REMOVE = 3;
    /**
     * If NodeVisitor::enterNode() returns DONT_TRAVERSE_CURRENT_AND_CHILDREN, child nodes of the current node will not
     * be traversed for any visitors.
     *
     * For subsequent visitors enterNode() will not be called as well. leaveNode() will be invoked for visitors that has
     * enterNode() method invoked.
     *
     * @api
     * @var int
     */
    public const DONT_TRAVERSE_CURRENT_AND_CHILDREN = 4;
    /**
     * @var bool Whether traversal should be stopped
     */
    private bool $stop_traversal = \false;
    /**
     * @var PhpDocNodeVisitorInterface[]
     */
    private array $php_doc_node_visitors = [];
    public function add_php_doc_node_visitor(Php_Doc_Node_Visitor_Interface $php_doc_node_visitor): void
    {
        $this->php_doc_node_visitors[] = $php_doc_node_visitor;
    }
    public function traverse(Node $node): void
    {
        foreach ($this->php_doc_node_visitors as $php_doc_node_visitor) {
            $php_doc_node_visitor->before_traverse($node);
        }
        $node = $this->traverse_node($node);
        foreach ($this->php_doc_node_visitors as $php_doc_node_visitor) {
            $php_doc_node_visitor->after_traverse($node);
        }
    }
    /**
     * @param callable(Node $node): (int|null|Node) $callable
     */
    public function traverse_with_callable(Node $node, string $doc_content, callable $callable): void
    {
        $callable_php_doc_node_visitor = new Callable_Php_Doc_Node_Visitor($callable, $doc_content);
        $this->add_php_doc_node_visitor($callable_php_doc_node_visitor);
        $this->traverse($node);
    }
    /**
     * @template TNode of Node
     * @param TNode $node
     * @return TNode
     */
    private function traverse_node(Node $node): Node
    {
        $object_public_properties_to_values = get_object_vars($node);
        $sub_node_names = array_keys($object_public_properties_to_values);
        foreach ($sub_node_names as $sub_node_name) {
            $sub_node =& $node->{$sub_node_name};
            if (\is_array($sub_node)) {
                $sub_node = $this->traverse_array($sub_node);
            } elseif ($sub_node instanceof Node) {
                $break_visitor_index = null;
                $traverse_children = \true;
                foreach ($this->php_doc_node_visitors as $visitor_index => $php_doc_node_visitor) {
                    $return = $php_doc_node_visitor->enter_node($sub_node);
                    if ($return !== null) {
                        if ($return instanceof Node) {
                            $sub_node = $return;
                        } elseif ($return === self::DONT_TRAVERSE_CHILDREN) {
                            $traverse_children = \false;
                        } elseif ($return === self::DONT_TRAVERSE_CURRENT_AND_CHILDREN) {
                            $traverse_children = \false;
                            $break_visitor_index = $visitor_index;
                            break;
                        } elseif ($return === self::STOP_TRAVERSAL) {
                            $this->stop_traversal = \true;
                        } elseif ($return === self::NODE_REMOVE) {
                            unset($sub_node);
                            continue 2;
                        } else {
                            throw new Invalid_Traverse_Exception('enterNode() returned invalid value of type ' . gettype($return));
                        }
                    }
                }
                if ($traverse_children) {
                    $sub_node = $this->traverse_node($sub_node);
                    if ($this->stop_traversal) {
                        break;
                    }
                }
                foreach ($this->php_doc_node_visitors as $visitor_index => $php_doc_node_visitor) {
                    $php_doc_node_visitor->leave_node($sub_node);
                    if ($break_visitor_index === $visitor_index) {
                        break;
                    }
                }
            }
        }
        return $node;
    }
    /**
     * @param array<Node|mixed> $nodes
     * @return array<Node|mixed>
     */
    private function traverse_array(array $nodes): array
    {
        foreach ($nodes as $key => &$node) {
            // can be string or something else
            if (!$node instanceof Node) {
                continue;
            }
            $traverse_children = \true;
            $break_visitor_index = null;
            foreach ($this->php_doc_node_visitors as $visitor_index => $php_doc_node_visitor) {
                $return = $php_doc_node_visitor->enter_node($node);
                if ($return !== null) {
                    if ($return instanceof Node) {
                        $node = $return;
                    } elseif ($return === self::DONT_TRAVERSE_CHILDREN) {
                        $traverse_children = \false;
                    } elseif ($return === self::DONT_TRAVERSE_CURRENT_AND_CHILDREN) {
                        $traverse_children = \false;
                        $break_visitor_index = $visitor_index;
                        break;
                    } elseif ($return === self::STOP_TRAVERSAL) {
                        $this->stop_traversal = \true;
                    } elseif ($return === self::NODE_REMOVE) {
                        // remove node
                        unset($nodes[$key]);
                        continue 2;
                    } else {
                        throw new Invalid_Traverse_Exception('enterNode() returned invalid value of type ' . gettype($return));
                    }
                }
            }
            // should traverse node children's properties?
            if ($traverse_children) {
                $node = $this->traverse_node($node);
                if ($this->stop_traversal) {
                    break;
                }
            }
            foreach ($this->php_doc_node_visitors as $visitor_index => $php_doc_node_visitor) {
                $return = $php_doc_node_visitor->leave_node($node);
                if ($return !== null) {
                    if ($return instanceof Node) {
                        $node = $return;
                    } elseif (\is_array($return)) {
                        $do_nodes[] = [$key, $return];
                        break;
                    } elseif ($return === self::NODE_REMOVE) {
                        $do_nodes[] = [$key, []];
                        break;
                    } elseif ($return === self::STOP_TRAVERSAL) {
                        $this->stop_traversal = \true;
                        break 2;
                    } else {
                        throw new Invalid_Traverse_Exception('leaveNode() returned invalid value of type ' . gettype($return));
                    }
                }
                if ($break_visitor_index === $visitor_index) {
                    break;
                }
            }
        }
        return $nodes;
    }
}