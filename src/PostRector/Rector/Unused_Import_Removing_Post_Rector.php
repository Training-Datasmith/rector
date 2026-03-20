<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Rector;

use Php_Parser\Comment;
use Php_Parser\Comment\Doc;
use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Stmt\Declare_;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node\Stmt\Nop;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
use Php_Parser\Node_Visitor;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Node\File_Node;
use Rector_Prefix202603\Nette\Utils\Strings;
final class Unused_Import_Removing_Post_Rector extends \Rector\Post_Rector\Rector\Abstract_Post_Rector
{
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    public function __construct(Simple_Callable_Node_Traverser $simple_callable_node_traverser, Php_Doc_Info_Factory $php_doc_info_factory)
    {
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
        $this->php_doc_info_factory = $php_doc_info_factory;
    }
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Namespace_ && !$node instanceof File_Node) {
            return null;
        }
        $has_changed = \false;
        $namespace_original_case = $node instanceof Namespace_ && $node->name instanceof Name ? $node->name->to_string() : null;
        $names_in_original_case = $this->resolve_used_php_and_doc_names($node);
        $names_in_lower_case = array_map(\Closure::from_callable('strtolower'), $names_in_original_case);
        $first_stmt_key = 0;
        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof Declare_ && $key === 0) {
                ++$first_stmt_key;
            }
            if (!$stmt instanceof Use_) {
                continue;
            }
            if ($stmt->uses === [] || $names_in_original_case === []) {
                unset($node->stmts[$key]);
                $has_changed = \true;
                continue;
            }
            $is_case_sensitive = $stmt->type === Use_::TYPE_CONSTANT;
            $names = $is_case_sensitive ? $names_in_original_case : $names_in_lower_case;
            $namespace_name = $namespace_original_case === null ? null : ($is_case_sensitive ? $namespace_original_case : strtolower($namespace_original_case));
            foreach ($stmt->uses as $use_use_key => $use_use) {
                if ($this->is_use_import_used($use_use, $is_case_sensitive, $names, $namespace_name)) {
                    continue;
                }
                unset($stmt->uses[$use_use_key]);
                $has_changed = \true;
            }
            if ($stmt->uses === []) {
                $comments = $node->stmts[$key]->get_comments();
                if ($key === $first_stmt_key && $comments !== []) {
                    $node->stmts[$key] = new Nop();
                    $node->stmts[$key]->set_attribute(Attribute_Key::COMMENTS, $comments);
                } else {
                    unset($node->stmts[$key]);
                }
            }
        }
        if ($has_changed === \false) {
            return null;
        }
        $this->add_rector_class_with_line($node);
        $node->stmts = array_values($node->stmts);
        return $node;
    }
    /**
     * @return string[]
     * @param \PhpParser\Node\Stmt\Namespace_|\Rector\PhpParser\Node\FileNode $fileNode
     */
    private function find_non_use_import_names($file_node): array
    {
        $names = [];
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($file_node->stmts, static function (Node $node) use (&$names) {
            if ($node instanceof Use_) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Name) {
                return null;
            }
            if ($node instanceof Fully_Qualified) {
                $original_name = $node->get_attribute(Attribute_Key::ORIGINAL_NAME);
                if ($original_name instanceof Name) {
                    // collect original Name as cover namespaced used
                    $names[] = $original_name->to_string();
                    return $node;
                }
            }
            $names[] = $node->to_string();
            return $node;
        });
        return $names;
    }
    /**
     * @return string[]
     * @param \PhpParser\Node\Stmt\Namespace_|\Rector\PhpParser\Node\FileNode $rootNode
     */
    private function find_names_in_doc_blocks($root_node): array
    {
        $names = [];
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($root_node, function (Node $node) use (&$names) {
            $comments = $node->get_comments();
            if ($comments === []) {
                return null;
            }
            $docs = array_filter($comments, static fn(Comment $comment): bool => $comment instanceof Doc);
            if ($docs === []) {
                return null;
            }
            $total_docs = count($docs);
            foreach ($docs as $doc) {
                $node_to_check = $total_docs === 1 ? $node : clone $node;
                if ($total_docs > 1) {
                    $node_to_check->set_doc_comment($doc);
                }
                $php_doc_info = $this->php_doc_info_factory->create_from_node_or_empty($node_to_check);
                $names = array_merge($names, $php_doc_info->get_annotation_class_names());
                $const_fetch_node_names = $php_doc_info->get_const_fetch_node_class_names();
                $names = array_merge($names, $const_fetch_node_names);
                $generic_tag_class_names = $php_doc_info->get_generic_tag_class_names();
                $names = array_merge($names, $generic_tag_class_names);
                $array_item_tag_class_names = $php_doc_info->get_array_item_node_class_names();
                $names = array_merge($names, $array_item_tag_class_names);
            }
        });
        return $names;
    }
    /**
     * @return string[]
     * @param \PhpParser\Node\Stmt\Namespace_|\Rector\PhpParser\Node\FileNode $rootNode
     */
    private function resolve_used_php_and_doc_names($root_node): array
    {
        $php_names = $this->find_non_use_import_names($root_node);
        $doc_block_names = $this->find_names_in_doc_blocks($root_node);
        $names = array_merge($php_names, $doc_block_names);
        return array_unique($names);
    }
    /**
     * @param string[] $names
     */
    private function is_use_import_used(Use_Item $use_item, bool $is_case_sensitive, array $names, ?string $namespace_name): bool
    {
        $compared_name = $use_item->alias instanceof Identifier ? $use_item->alias->to_string() : $use_item->name->to_string();
        if (!$is_case_sensitive) {
            $compared_name = strtolower($compared_name);
        }
        if (in_array($compared_name, $names, \true)) {
            return \true;
        }
        $last_name = Strings::after($compared_name, '\\', -1);
        $namespaced_prefix = $last_name . '\\';
        if ($namespaced_prefix === '\\') {
            $namespaced_prefix = $compared_name . '\\';
        }
        // match partial import
        foreach ($names as $name) {
            if (strncmp($name, '\\', strlen('\\')) === 0) {
                continue;
            }
            if ($this->is_sub_namespace($name, $compared_name, $namespaced_prefix)) {
                return \true;
            }
            if (strncmp($name, $last_name . '\\', strlen($last_name . '\\')) !== 0) {
                if (strncmp($name, $compared_name . '\\', strlen($compared_name . '\\')) === 0) {
                    return \true;
                }
                continue;
            }
            if ($namespace_name === null) {
                return \true;
            }
            if (strncmp($name, $namespace_name . '\\', strlen($namespace_name . '\\')) !== 0) {
                return \true;
            }
        }
        return \false;
    }
    private function is_sub_namespace(string $name, string $compared_name, string $namespaced_prefix): bool
    {
        if (substr_compare($compared_name, '\\' . $name, -strlen('\\' . $name)) === 0) {
            return \true;
        }
        if (strncmp($name, $namespaced_prefix, strlen($namespaced_prefix)) === 0) {
            $sub_namespace = (string) substr($name, strlen($namespaced_prefix));
            return strpos($sub_namespace, '\\') === \false;
        }
        return \false;
    }
}