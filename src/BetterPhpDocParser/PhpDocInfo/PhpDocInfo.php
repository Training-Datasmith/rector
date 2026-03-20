<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Info;

use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Fetch_Node;
use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Extends_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Generic_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Implements_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Invalid_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Method_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Param_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Child_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Property_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Return_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Var_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Const_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
use Rector\Better_Php_Doc_Parser\Annotation\Annotation_Naming;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Spaceless_Php_Doc_Tag_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Finder\Php_Doc_Node_By_Type_Finder;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Shortened_Identifier_Type_Node;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
/**
 * @see \Rector\Tests\BetterPhpDocParser\PhpDocInfo\PhpDocInfo\PhpDocInfoTest
 */
final class Php_Doc_Info
{
    /**
     * @readonly
     */
    private Php_Doc_Node $php_doc_node;
    /**
     * @readonly
     */
    private Better_Token_Iterator $better_token_iterator;
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private \Php_Parser\Node $node;
    /**
     * @readonly
     */
    private Annotation_Naming $annotation_naming;
    /**
     * @readonly
     */
    private Php_Doc_Node_By_Type_Finder $php_doc_node_by_type_finder;
    /**
     * @var array<class-string<PhpDocTagValueNode>, string>
     */
    private const TAGS_TYPES_TO_NAMES = [Return_Tag_Value_Node::class => '@return', Param_Tag_Value_Node::class => '@param', Var_Tag_Value_Node::class => '@var', Method_Tag_Value_Node::class => '@method', Property_Tag_Value_Node::class => '@property', Extends_Tag_Value_Node::class => '@extends', Implements_Tag_Value_Node::class => '@implements'];
    private bool $is_single_line = \false;
    /**
     * @readonly
     */
    private Php_Doc_Node $original_php_doc_node;
    public function __construct(Php_Doc_Node $php_doc_node, Better_Token_Iterator $better_token_iterator, Static_Type_Mapper $static_type_mapper, \Php_Parser\Node $node, Annotation_Naming $annotation_naming, Php_Doc_Node_By_Type_Finder $php_doc_node_by_type_finder)
    {
        $this->php_doc_node = $php_doc_node;
        $this->better_token_iterator = $better_token_iterator;
        $this->static_type_mapper = $static_type_mapper;
        $this->node = $node;
        $this->annotation_naming = $annotation_naming;
        $this->php_doc_node_by_type_finder = $php_doc_node_by_type_finder;
        $this->original_php_doc_node = clone $php_doc_node;
        if (!$better_token_iterator->contains_token_type(Lexer::TOKEN_PHPDOC_EOL)) {
            $this->is_single_line = \true;
        }
    }
    /**
     * @api
     */
    public function add_php_doc_tag_node(Php_Doc_Child_Node $php_doc_child_node): void
    {
        $this->php_doc_node->children[] = $php_doc_child_node;
        // to give node more space
        $this->make_multi_lined();
    }
    public function get_php_doc_node(): Php_Doc_Node
    {
        return $this->php_doc_node;
    }
    public function get_original_php_doc_node(): Php_Doc_Node
    {
        return $this->original_php_doc_node;
    }
    /**
     * @return list<array{string, int, int}>
     */
    public function get_tokens(): array
    {
        return $this->better_token_iterator->get_tokens();
    }
    public function get_token_count(): int
    {
        return $this->better_token_iterator->count();
    }
    public function get_var_tag_value_node(string $tag_name = '@var'): ?Var_Tag_Value_Node
    {
        return $this->php_doc_node->get_var_tag_values($tag_name)[0] ?? null;
    }
    /**
     * @return array<PhpDocTagNode>
     */
    public function get_tags_by_name(string $name): array
    {
        // for simple tag names only
        if (strpos($name, '\\') !== \false) {
            return [];
        }
        $tags = $this->php_doc_node->get_tags();
        $name = $this->annotation_naming->normalize_name($name);
        $tags = array_filter($tags, static fn(Php_Doc_Tag_Node $php_doc_tag_node): bool => $php_doc_tag_node->name === $name);
        return array_values($tags);
    }
    public function get_param_type(string $name): Type
    {
        $param_tag_value_nodes = $this->get_param_tag_value_by_name($name);
        return $this->get_type_or_mixed($param_tag_value_nodes);
    }
    /**
     * @return ParamTagValueNode[]
     */
    public function get_param_tag_value_nodes(): array
    {
        return $this->php_doc_node->get_param_tag_values();
    }
    public function get_var_type(string $tag_name = '@var'): Type
    {
        return $this->get_type_or_mixed($this->get_var_tag_value_node($tag_name));
    }
    public function get_return_type(): Type
    {
        return $this->get_type_or_mixed($this->get_return_tag_value());
    }
    /**
     * @param class-string<Node> $type
     */
    public function has_by_type(string $type): bool
    {
        return $this->php_doc_node_by_type_finder->find_by_type($this->php_doc_node, $type) !== [];
    }
    /**
     * @param array<class-string<Node>> $types
     */
    public function has_by_types(array $types): bool
    {
        foreach ($types as $type) {
            if ($this->has_by_type($type)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param string[] $names
     */
    public function has_by_names(array $names): bool
    {
        foreach ($names as $name) {
            if ($this->has_by_name($name)) {
                return \true;
            }
        }
        return \false;
    }
    public function has_by_name(string $name): bool
    {
        return (bool) $this->get_tags_by_name($name);
    }
    /**
     * @api
     */
    public function get_by_name(string $name): ?Node
    {
        return $this->get_tags_by_name($name)[0] ?? null;
    }
    /**
     * @param string[] $classes
     */
    public function get_by_annotation_classes(array $classes): ?Doctrine_Annotation_Tag_Value_Node
    {
        $doctrine_annotation_tag_value_nodes = $this->php_doc_node_by_type_finder->find_doctrine_annotations_by_classes($this->php_doc_node, $classes);
        return $doctrine_annotation_tag_value_nodes[0] ?? null;
    }
    /**
     * @api doctrine/symfony
     */
    public function get_by_annotation_class(string $class): ?Doctrine_Annotation_Tag_Value_Node
    {
        $doctrine_annotation_tag_value_nodes = $this->php_doc_node_by_type_finder->find_doctrine_annotations_by_class($this->php_doc_node, $class);
        return $doctrine_annotation_tag_value_nodes[0] ?? null;
    }
    public function has_by_annotation_class(string $class): bool
    {
        return $this->find_by_annotation_class($class) !== [];
    }
    /**
     * @param string[] $annotationsClasses
     */
    public function has_by_annotation_classes(array $annotations_classes): bool
    {
        return $this->get_by_annotation_classes($annotations_classes) instanceof Doctrine_Annotation_Tag_Value_Node;
    }
    public function find_one_by_annotation_class(string $desired_class): ?Doctrine_Annotation_Tag_Value_Node
    {
        $found_tag_value_nodes = $this->find_by_annotation_class($desired_class);
        return $found_tag_value_nodes[0] ?? null;
    }
    /**
     * @template T of \PHPStan\PhpDocParser\Ast\Node
     * @param class-string<T> $typeToRemove
     */
    public function remove_by_type(string $type_to_remove, ?string $name = null): bool
    {
        $has_changed = \false;
        if ($name === '') {
            $name = null;
        }
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $php_doc_node_traverser->traverse_with_callable($this->php_doc_node, '', static function (Node $node) use ($type_to_remove, &$has_changed, $name): ?int {
            if ($node instanceof Php_Doc_Tag_Node && $node->value instanceof $type_to_remove) {
                // keep special annotation for tools
                if (strncmp($node->name, '@psalm-', strlen('@psalm-')) === 0) {
                    return null;
                }
                if (strncmp($node->name, '@phpstan-', strlen('@phpstan-')) === 0) {
                    return null;
                }
                if ($name !== null && $node->value instanceof Var_Tag_Value_Node && $node->value->variable_name !== '$' . ltrim($name, '$')) {
                    return Php_Doc_Node_Traverser::DONT_TRAVERSE_CHILDREN;
                }
                $has_changed = \true;
                return Php_Doc_Node_Traverser::NODE_REMOVE;
            }
            if (!$node instanceof $type_to_remove) {
                return null;
            }
            $has_changed = \true;
            return Php_Doc_Node_Traverser::NODE_REMOVE;
        });
        return $has_changed;
    }
    public function remove_by_name(string $tag_name): bool
    {
        $tag_name = '@' . ltrim($tag_name, '@');
        $has_changed = \false;
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $php_doc_node_traverser->traverse_with_callable($this->php_doc_node, '', static function (Node $node) use ($tag_name, &$has_changed): ?int {
            if ($node instanceof Php_Doc_Tag_Node && $node->name === $tag_name) {
                $has_changed = \true;
                return Php_Doc_Node_Traverser::NODE_REMOVE;
            }
            return null;
        });
        return $has_changed;
    }
    public function add_tag_value_node(Php_Doc_Tag_Value_Node $php_doc_tag_value_node): void
    {
        if ($php_doc_tag_value_node instanceof Doctrine_Annotation_Tag_Value_Node) {
            if ($php_doc_tag_value_node->identifier_type_node instanceof Shortened_Identifier_Type_Node) {
                $name = '@' . $php_doc_tag_value_node->identifier_type_node;
            } else {
                $name = '@\\' . $php_doc_tag_value_node->identifier_type_node;
            }
            $spaceless_php_doc_tag_node = new Spaceless_Php_Doc_Tag_Node($name, $php_doc_tag_value_node);
            $this->add_php_doc_tag_node($spaceless_php_doc_tag_node);
            return;
        }
        $name = $this->resolve_name_for_php_doc_tag_value_node($php_doc_tag_value_node);
        if (!is_string($name)) {
            throw new Should_Not_Happen_Exception(sprintf('Name could not be resolved for "%s" tag value node. Complete it to %s::TAGS_TYPES_TO_NAMES constant', get_class($php_doc_tag_value_node), self::class));
        }
        $php_doc_tag_node = new Php_Doc_Tag_Node($name, $php_doc_tag_value_node);
        $this->add_php_doc_tag_node($php_doc_tag_node);
    }
    public function is_new_node(): bool
    {
        if ($this->php_doc_node->children === []) {
            return \false;
        }
        return $this->better_token_iterator->count() === 0;
    }
    public function is_single_line(): bool
    {
        return $this->is_single_line;
    }
    public function has_invalid_tag(string $name): bool
    {
        // fallback for invalid tag value node
        foreach ($this->php_doc_node->children as $php_doc_child_node) {
            if (!$php_doc_child_node instanceof Php_Doc_Tag_Node) {
                continue;
            }
            if ($php_doc_child_node->name !== $name) {
                continue;
            }
            if (!$php_doc_child_node->value instanceof Invalid_Tag_Value_Node) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    public function get_return_tag_value(): ?Return_Tag_Value_Node
    {
        $return_tag_value_nodes = $this->php_doc_node->get_return_tag_values();
        return $return_tag_value_nodes[0] ?? null;
    }
    public function get_param_tag_value_by_name(string $name): ?Param_Tag_Value_Node
    {
        $desired_param_name_with_dollar = '$' . ltrim($name, '$');
        foreach ($this->get_param_tag_value_nodes() as $param_tag_value_node) {
            if ($param_tag_value_node->parameter_name !== $desired_param_name_with_dollar) {
                continue;
            }
            return $param_tag_value_node;
        }
        return null;
    }
    /**
     * @return string[]
     */
    public function get_template_names(): array
    {
        $template_names = [];
        foreach ($this->php_doc_node->get_template_tag_values() as $template_tag_value_node) {
            $template_names[] = $template_tag_value_node->name;
        }
        return $template_names;
    }
    public function make_multi_lined(): void
    {
        $this->is_single_line = \false;
    }
    public function get_node(): \Php_Parser\Node
    {
        return $this->node;
    }
    /**
     * @return string[]
     */
    public function get_annotation_class_names(): array
    {
        /** @var IdentifierTypeNode[] $identifierTypeNodes */
        $identifier_type_nodes = $this->php_doc_node_by_type_finder->find_by_type($this->php_doc_node, Identifier_Type_Node::class);
        $resolved_classes = [];
        foreach ($identifier_type_nodes as $identifier_type_node) {
            $resolved_classes[] = ltrim($identifier_type_node->name, '@');
        }
        return $resolved_classes;
    }
    /**
     * @return string[]
     */
    public function get_generic_tag_class_names(): array
    {
        /** @var GenericTagValueNode[] $genericTagValueNodes */
        $generic_tag_value_nodes = $this->php_doc_node_by_type_finder->find_by_type($this->php_doc_node, Generic_Tag_Value_Node::class);
        $resolved_classes = [];
        foreach ($generic_tag_value_nodes as $generic_tag_value_node) {
            if ($generic_tag_value_node->value === '') {
                continue;
            }
            // add default original value
            $resolved_classes[] = $generic_tag_value_node->value;
            if (strpos($generic_tag_value_node->value, '::') === \false) {
                continue;
            }
            // add resolved class name if any
            $resolved_class = $generic_tag_value_node->get_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS);
            if ($resolved_class === null) {
                $resolved_classes[] = $generic_tag_value_node->value;
                continue;
            }
            $resolved_classes[] = $resolved_class;
        }
        return $resolved_classes;
    }
    /**
     * @return string[]
     */
    public function get_const_fetch_node_class_names(): array
    {
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $class_names = [];
        $php_doc_node_traverser->traverse_with_callable($this->php_doc_node, '', static function (Node $node) use (&$class_names): ?Const_Type_Node {
            if (!$node instanceof Const_Type_Node) {
                return null;
            }
            if (!$node->const_expr instanceof Const_Fetch_Node) {
                return null;
            }
            $class_names[] = $node->const_expr->get_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS);
            return $node;
        });
        return $class_names;
    }
    /**
     * @return string[]
     */
    public function get_array_item_node_class_names(): array
    {
        $php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $class_names = [];
        $php_doc_node_traverser->traverse_with_callable($this->php_doc_node, '', static function (Node $node) use (&$class_names): ?Array_Item_Node {
            if (!$node instanceof Array_Item_Node) {
                return null;
            }
            $resolved_class = $node->get_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS);
            if ($resolved_class === null) {
                return null;
            }
            $class_names[] = $resolved_class;
            return $node;
        });
        return $class_names;
    }
    /**
     * @param class-string $desiredClass
     * @return DoctrineAnnotationTagValueNode[]
     */
    public function find_by_annotation_class(string $desired_class): array
    {
        return $this->php_doc_node_by_type_finder->find_doctrine_annotations_by_class($this->php_doc_node, $desired_class);
    }
    private function resolve_name_for_php_doc_tag_value_node(Php_Doc_Tag_Value_Node $php_doc_tag_value_node): ?string
    {
        foreach (self::TAGS_TYPES_TO_NAMES as $tag_value_node_type => $name) {
            /** @var class-string<PhpDocTagValueNode> $tagValueNodeType */
            if ($php_doc_tag_value_node instanceof $tag_value_node_type) {
                return $name;
            }
        }
        return null;
    }
    /**
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\Type
     */
    private function get_type_or_mixed(?Php_Doc_Tag_Value_Node $php_doc_tag_value_node)
    {
        if (!$php_doc_tag_value_node instanceof Php_Doc_Tag_Value_Node) {
            return new Mixed_Type();
        }
        return $this->static_type_mapper->map_php_stan_php_doc_type_to_php_stan_type($php_doc_tag_value_node, $this->node);
    }
}