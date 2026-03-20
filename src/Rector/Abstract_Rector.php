<?php

declare (strict_types=1);
namespace Rector\Rector;

use Php_Parser\Node;
use Php_Parser\Node\Name;
use Php_Parser\Node\Property_Item;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Const_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Trait_;
use Php_Parser\Node_Visitor;
use Php_Parser\Node_Visitor_Abstract;
use Php_Stan\Analyser\Mutating_Scope;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Rector\Application\Changed_Node_Scope_Refresher;
use Rector\Application\Provider\Current_File_Provider;
use Rector\Better_Php_Doc_Parser\Comment\Comments_Merger;
use Rector\Changes_Reporting\Value_Object\Rector_With_Line_Change;
use Rector\Contract\Rector\Html_Averse_Rector_Interface;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Decorator\Created_By_Rule_Decorator;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Comparing\Node_Comparator;
use Rector\Php_Parser\Node\Node_Factory;
use Rector\Skipper\Skipper\Skipper;
use Rector\Value_Object\Application\File;
abstract class Abstract_Rector extends Node_Visitor_Abstract implements Rector_Interface
{
    /**
     * @var string
     */
    private const EMPTY_NODE_ARRAY_MESSAGE = <<<CODE_SAMPLE
    Array of nodes cannot be empty. Ensure "%s->refactor()" returns non-empty array for Nodes.
    
    A) Direct return null for no change:
    
        return null;
    
    B) Remove the Node:
    
        return \\PhpParser\\NodeVisitor::REMOVE_NODE;
    CODE_SAMPLE;
    protected Node_Name_Resolver $node_name_resolver;
    protected Node_Type_Resolver $node_type_resolver;
    protected Node_Factory $node_factory;
    protected Node_Comparator $node_comparator;
    protected File $file;
    protected Skipper $skipper;
    private Changed_Node_Scope_Refresher $changed_node_scope_refresher;
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    private Current_File_Provider $current_file_provider;
    private Comments_Merger $comments_merger;
    private Created_By_Rule_Decorator $created_by_rule_decorator;
    public function autowire(Node_Name_Resolver $node_name_resolver, Node_Type_Resolver $node_type_resolver, Simple_Callable_Node_Traverser $simple_callable_node_traverser, Node_Factory $node_factory, Skipper $skipper, Node_Comparator $node_comparator, Current_File_Provider $current_file_provider, Created_By_Rule_Decorator $created_by_rule_decorator, Changed_Node_Scope_Refresher $changed_node_scope_refresher, Comments_Merger $comments_merger): void
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->node_type_resolver = $node_type_resolver;
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
        $this->node_factory = $node_factory;
        $this->skipper = $skipper;
        $this->node_comparator = $node_comparator;
        $this->current_file_provider = $current_file_provider;
        $this->created_by_rule_decorator = $created_by_rule_decorator;
        $this->changed_node_scope_refresher = $changed_node_scope_refresher;
        $this->comments_merger = $comments_merger;
    }
    /**
     * @final Avoid override to prevent unintended side-effects. Use enterNode() or @see \Rector\Contract\PhpParser\DecoratingNodeVisitorInterface instead.
     *
     * @internal
     *
     * @return Node[]|null
     */
    public function before_traverse(array $nodes): ?array
    {
        // workaround for file around refactor()
        $file = $this->current_file_provider->get_file();
        if (!$file instanceof File) {
            throw new Should_Not_Happen_Exception('File object is missing. Make sure you call $this->currentFileProvider->setFile(...) before traversing.');
        }
        $this->file = $file;
        return null;
    }
    /**
     * @return NodeVisitor::REMOVE_NODE|Node|null|Node[]
     */
    final public function enter_node(Node $node)
    {
        if (is_a($this, Html_Averse_Rector_Interface::class, \true) && $this->file->contains_html()) {
            return null;
        }
        $file_path = $this->file->get_file_path();
        if ($this->skipper->should_skip_current_node($this, $file_path, static::class, $node)) {
            return null;
        }
        // ensure origNode pulled before refactor to avoid changed during refactor, ref https://3v4l.org/YMEGN
        $original_node = $node->get_attribute(Attribute_Key::ORIGINAL_NODE) ?? $node;
        $refactored_node_or_state = $this->refactor($node);
        // nothing to change → continue
        if ($refactored_node_or_state === null) {
            return null;
        }
        if ($refactored_node_or_state === []) {
            $error_message = sprintf(self::EMPTY_NODE_ARRAY_MESSAGE, static::class);
            throw new Should_Not_Happen_Exception($error_message);
        }
        $is_state = is_int($refactored_node_or_state);
        if ($is_state) {
            $this->created_by_rule_decorator->decorate($node, $original_node, static::class);
            // only remove node is supported
            if ($refactored_node_or_state !== Node_Visitor::REMOVE_NODE) {
                // @todo warn about unsupported state in the future
                return null;
            }
            // notify this rule changed code
            $rector_with_line_change = new Rector_With_Line_Change(static::class, $original_node->get_start_line());
            $this->file->add_rector_class_with_line($rector_with_line_change);
            return $refactored_node_or_state;
        }
        return $this->post_refactor_process($original_node, $node, $refactored_node_or_state, $file_path);
    }
    /**
     * @deprecated no longer used
     * @return mixed[]|int|\PhpParser\Node|null
     */
    final public function leave_node(Node $node)
    {
        return null;
    }
    protected function is_name(Node $node, string $name): bool
    {
        return $this->node_name_resolver->is_name($node, $name);
    }
    /**
     * @param string[] $names
     */
    protected function is_names(Node $node, array $names): bool
    {
        return $this->node_name_resolver->is_names($node, $names);
    }
    /**
     * Some nodes have always-known string name. This makes PHPStan smarter.
     * @see https://phpstan.org/writing-php-code/phpdoc-types#conditional-return-types
     *
     * @return ($node is Node\Param ? string :
     *  ($node is ClassMethod ? string :
     *  ($node is Property ? string :
     *  ($node is PropertyItem ? string :
     *  ($node is Trait_ ? string :
     *  ($node is Interface_ ? string :
     *  ($node is Const_ ? string :
     *  ($node is Node\Const_ ? string :
     *  ($node is Name ? string :
     *      string|null )))))))))
     */
    protected function get_name(Node $node): ?string
    {
        return $this->node_name_resolver->get_name($node);
    }
    protected function is_object_type(Node $node, Object_Type $object_type): bool
    {
        return $this->node_type_resolver->is_object_type($node, $object_type);
    }
    /**
     * Use this method for getting expr|node type
     */
    protected function get_type(Node $node): Type
    {
        return $this->node_type_resolver->get_type($node);
    }
    /**
     * @param Node|Node[] $nodes
     * @param callable(Node): (int|Node|null|Node[]) $callable
     */
    protected function traverse_nodes_with_callable($nodes, callable $callable): void
    {
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($nodes, $callable);
    }
    protected function mirror_comments(Node $new_node, Node $old_node): void
    {
        $this->comments_merger->mirror_comments($new_node, $old_node);
    }
    /**
     * @param Node|Node[] $refactoredNode
     * @return Node|Node[]
     */
    private function post_refactor_process(Node $original_node, Node $node, $refactored_node, string $file_path)
    {
        /** @var non-empty-array<Node>|Node $refactoredNode */
        $this->created_by_rule_decorator->decorate($refactored_node, $original_node, static::class);
        $rector_with_line_change = new Rector_With_Line_Change(static::class, $original_node->get_start_line());
        $this->file->add_rector_class_with_line($rector_with_line_change);
        /** @var MutatingScope|null $currentScope */
        $current_scope = $node->get_attribute(Attribute_Key::SCOPE);
        $this->refresh_scope_nodes($refactored_node, $file_path, $current_scope);
        return $refactored_node;
    }
    /**
     * @param Node[]|Node $node
     */
    private function refresh_scope_nodes($node, string $file_path, ?Mutating_Scope $mutating_scope): void
    {
        $nodes = $node instanceof Node ? [$node] : $node;
        foreach ($nodes as $node) {
            $this->changed_node_scope_refresher->refresh($node, $file_path, $mutating_scope);
        }
    }
}