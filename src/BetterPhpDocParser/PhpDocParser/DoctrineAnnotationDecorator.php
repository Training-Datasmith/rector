<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Doctrine\Doctrine_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Generic_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Invalid_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Child_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Text_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Php_Stan\Type\Object_Type;
use Rector\Better_Php_Doc_Parser\Attributes\Attribute_Mirrorer;
use Rector\Better_Php_Doc_Parser\Contract\Php_Doc_Parser\Php_Doc_Node_Decorator_Interface;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Spaceless_Php_Doc_Tag_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Token_Iterator_Factory;
use Rector\Better_Php_Doc_Parser\Value_Object\Doctrine_Annotation\Silent_Key_Map;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Better_Php_Doc_Parser\Value_Object\Start_And_End;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type;
use Rector\Type_Declaration\Php_Stan\Object_Type_Specifier;
use Rector\Util\String_Utils;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Doctrine_Annotation_Decorator implements Php_Doc_Node_Decorator_Interface
{
    /**
     * @readonly
     */
    private \Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Class_Annotation_Matcher $class_annotation_matcher;
    /**
     * @readonly
     */
    private \Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser $static_doctrine_annotation_parser;
    /**
     * @readonly
     */
    private Token_Iterator_Factory $token_iterator_factory;
    /**
     * @readonly
     */
    private Attribute_Mirrorer $attribute_mirrorer;
    /**
     * @readonly
     */
    private Object_Type_Specifier $object_type_specifier;
    /**
     * @see https://regex101.com/r/bGp2V0/2
     * @var string
     */
    public const LONG_ANNOTATION_REGEX = '#@\\\\(?<class_name>.*?)(?<annotation_content>\(.*?\)|,|\r?\n|$)#';
    /**
     * Special short annotations, that are resolved as FQN by Doctrine annotation parser
     * @var string[]
     */
    private const ALLOWED_SHORT_ANNOTATIONS = ['Target'];
    /**
     * @see https://regex101.com/r/xWaLOz/1
     * @var string
     */
    private const NESTED_ANNOTATION_END_REGEX = '#(\s+)?\}\)(\s+)?#';
    /**
     * @see https://regex101.com/r/8rWY4r/1
     * @var string
     */
    private const NEWLINE_ANNOTATION_FQCN_REGEX = '#\r?\n@\\\\#';
    /**
     * @see https://regex101.com/r/3zXEh7/1
     * @var string
     */
    private const STAR_COMMENT_REGEX = '#^\s*\*#ms';
    public function __construct(\Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Class_Annotation_Matcher $class_annotation_matcher, \Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser $static_doctrine_annotation_parser, Token_Iterator_Factory $token_iterator_factory, Attribute_Mirrorer $attribute_mirrorer, Object_Type_Specifier $object_type_specifier)
    {
        $this->class_annotation_matcher = $class_annotation_matcher;
        $this->static_doctrine_annotation_parser = $static_doctrine_annotation_parser;
        $this->token_iterator_factory = $token_iterator_factory;
        $this->attribute_mirrorer = $attribute_mirrorer;
        $this->object_type_specifier = $object_type_specifier;
    }
    public function decorate(Php_Doc_Node $php_doc_node, Node $php_node): void
    {
        // merge split doctrine nested tags
        $this->merge_nested_doctrine_annotations($php_doc_node);
        $this->transform_generic_tag_value_nodes_to_doctrine_annotation_tag_value_nodes($php_doc_node, $php_node);
    }
    /**
     * Join token iterator with all the following nodes if nested
     */
    private function merge_nested_doctrine_annotations(Php_Doc_Node $php_doc_node): void
    {
        $removed_keys = [];
        foreach ($php_doc_node->children as $key => $php_doc_child_node) {
            if (in_array($key, $removed_keys, \true)) {
                continue;
            }
            if (!$php_doc_child_node instanceof Php_Doc_Tag_Node) {
                continue;
            }
            if (!$php_doc_child_node->value instanceof Generic_Tag_Value_Node) {
                continue;
            }
            $generic_tag_value_node = $php_doc_child_node->value;
            while (isset($php_doc_node->children[$key])) {
                ++$key;
                // no more next nodes
                if (!isset($php_doc_node->children[$key])) {
                    break;
                }
                $next_php_doc_child_node = $php_doc_node->children[$key];
                if ($next_php_doc_child_node instanceof Php_Doc_Text_Node && String_Utils::is_match($next_php_doc_child_node->text, self::NESTED_ANNOTATION_END_REGEX)) {
                    // @todo how to detect previously opened brackets?
                    // probably local property with holding count of opened brackets
                    $composed_content = $generic_tag_value_node->value . \PHP_EOL . $next_php_doc_child_node->text;
                    $generic_tag_value_node->value = $composed_content;
                    $start_and_end = $this->combine_start_and_end($php_doc_child_node, $next_php_doc_child_node);
                    $php_doc_child_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
                    $removed_keys[] = $key;
                    $removed_keys[] = $key + 1;
                    continue;
                }
                if (!$next_php_doc_child_node instanceof Php_Doc_Tag_Node) {
                    continue;
                }
                if (!$next_php_doc_child_node->value instanceof Generic_Tag_Value_Node) {
                    continue;
                }
                if ($this->is_closed_content($generic_tag_value_node->value)) {
                    break;
                }
                $composed_content = $generic_tag_value_node->value . \PHP_EOL . $next_php_doc_child_node->name . $next_php_doc_child_node->value->value;
                // cleanup the next from closing
                $generic_tag_value_node->value = $composed_content;
                $start_and_end = $this->combine_start_and_end($php_doc_child_node, $next_php_doc_child_node);
                $php_doc_child_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
                $current_child_value_node = $php_doc_node->children[$key];
                if (!$current_child_value_node instanceof Php_Doc_Tag_Node) {
                    continue;
                }
                $current_generic_tag_value_node = $current_child_value_node->value;
                if (!$current_generic_tag_value_node instanceof Generic_Tag_Value_Node) {
                    continue;
                }
                $removed_keys[] = $key;
            }
        }
        foreach (array_keys($php_doc_node->children) as $key) {
            if (!in_array($key, $removed_keys, \true)) {
                continue;
            }
            unset($php_doc_node->children[$key]);
        }
    }
    private function process_text_spaceless_in_text_node(Php_Doc_Node $php_doc_node, Php_Doc_Text_Node $php_doc_text_node, Node $current_php_node, int $key): void
    {
        $spaceless_php_doc_tag_nodes = $this->resolve_fqn_annotation_spaceless_php_doc_tag_node($php_doc_text_node, $current_php_node);
        if ($spaceless_php_doc_tag_nodes === []) {
            return;
        }
        $texts = Strings::split($php_doc_text_node->text, self::NEWLINE_ANNOTATION_FQCN_REGEX);
        $other_text = $texts[0];
        if (strncmp((string) $other_text, '@\\', strlen('@\\')) !== 0 && trim((string) $other_text) !== '') {
            $php_doc_node->children[$key] = new Php_Doc_Text_Node($other_text);
            array_splice($php_doc_node->children, $key + 1, 0, $spaceless_php_doc_tag_nodes);
            return;
        }
        unset($php_doc_node->children[$key]);
        array_splice($php_doc_node->children, $key, 0, $spaceless_php_doc_tag_nodes);
    }
    private function transform_generic_tag_value_nodes_to_doctrine_annotation_tag_value_nodes(Php_Doc_Node $php_doc_node, Node $current_php_node): void
    {
        foreach ($php_doc_node->children as $key => $php_doc_child_node) {
            // the @\FQN use case
            if ($php_doc_child_node instanceof Php_Doc_Text_Node) {
                $this->process_text_spaceless_in_text_node($php_doc_node, $php_doc_child_node, $current_php_node, $key);
                continue;
            }
            if (!$php_doc_child_node instanceof Php_Doc_Tag_Node) {
                continue;
            }
            // single quoted got invalid tag, keep process
            if ($php_doc_child_node->value instanceof Invalid_Tag_Value_Node) {
                $name = ltrim($php_doc_child_node->name, '@');
                $values = $php_doc_child_node->value->value;
                $this->process_doctrine($current_php_node, $name, $php_doc_child_node, $php_doc_node, $key, $values);
            }
            // needs stable correct detection of full class name
            if ($php_doc_child_node->value instanceof Doctrine_Tag_Value_Node) {
                $name = ltrim($php_doc_child_node->name, '@');
                $values = implode(', ', $php_doc_child_node->value->annotation->arguments);
                $this->process_doctrine($current_php_node, $name, $php_doc_child_node, $php_doc_node, $key, $values);
                continue;
            }
            if (!$php_doc_child_node->value instanceof Generic_Tag_Value_Node) {
                $this->process_description_as_spaceless_php_doctag_node($php_doc_node, $php_doc_child_node, $current_php_node, $key);
                continue;
            }
            // known doc tag to annotation class
            $fully_qualified_annotation_class = $this->class_annotation_matcher->resolve_tag_fully_qualified_name($php_doc_child_node->name, $current_php_node);
            // not an annotations class
            if (strpos($fully_qualified_annotation_class, '\\') === \false && !in_array($fully_qualified_annotation_class, self::ALLOWED_SHORT_ANNOTATIONS, \true)) {
                continue;
            }
            while (isset($php_doc_node->children[$key]) && $php_doc_node->children[$key] !== $php_doc_child_node) {
                ++$key;
            }
            $php_doc_text_node = new Php_Doc_Text_Node($php_doc_child_node->value->value);
            $start_and_end = $php_doc_child_node->value->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
            if (!$start_and_end instanceof Start_And_End) {
                $spaceless_php_doc_tag_node = $this->create_spaceless_php_doc_tag_node($php_doc_child_node->name, $php_doc_child_node->value, $fully_qualified_annotation_class, $current_php_node);
                $this->attribute_mirrorer->mirror($php_doc_child_node, $spaceless_php_doc_tag_node);
                $php_doc_node->children[$key] = $spaceless_php_doc_tag_node;
                continue;
            }
            $php_doc_text_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
            $spaceless_php_doc_tag_nodes = $this->resolve_fqn_annotation_spaceless_php_doc_tag_node($php_doc_text_node, $current_php_node);
            if ($spaceless_php_doc_tag_nodes === []) {
                $spaceless_php_doc_tag_node = $this->create_spaceless_php_doc_tag_node($php_doc_child_node->name, $php_doc_child_node->value, $fully_qualified_annotation_class, $current_php_node);
                $this->attribute_mirrorer->mirror($php_doc_child_node, $spaceless_php_doc_tag_node);
                $php_doc_node->children[$key] = $spaceless_php_doc_tag_node;
                continue;
            }
            Assert::is_a_of($php_doc_node->children[$key], Php_Doc_Tag_Node::class);
            $texts = Strings::split($php_doc_child_node->value->value, self::NEWLINE_ANNOTATION_FQCN_REGEX);
            $php_doc_node->children[$key]->value = new Generic_Tag_Value_Node($texts[0]);
            $php_doc_node->children[$key]->value->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
            $spaceless_php_doc_tag_node = $this->create_spaceless_php_doc_tag_node($php_doc_node->children[$key]->name, $php_doc_node->children[$key]->value, $fully_qualified_annotation_class, $current_php_node);
            $this->attribute_mirrorer->mirror($php_doc_node->children[$key], $spaceless_php_doc_tag_node);
            $php_doc_node->children[$key] = $spaceless_php_doc_tag_node;
            // require to reprint the generic
            $php_doc_node->children[$key]->value->set_attribute(Php_Doc_Attribute_Key::ORIG_NODE, null);
            array_splice($php_doc_node->children, $key + 1, 0, $spaceless_php_doc_tag_nodes);
        }
    }
    /**
     * @param mixed $key
     */
    private function process_doctrine(Node $current_php_node, string $name, Php_Doc_Tag_Node $php_doc_tag_node, Php_Doc_Node $php_doc_node, $key, string $values): void
    {
        $type = $this->object_type_specifier->narrow_to_fully_qualified_or_aliased_object_type($current_php_node, new Object_Type($name), $current_php_node->get_attribute(Attribute_Key::SCOPE));
        $fully_qualified_annotation_class = null;
        if ($type instanceof Shortened_Object_Type || $type instanceof Aliased_Object_Type) {
            $fully_qualified_annotation_class = $type->get_fully_qualified_name();
        } elseif ($type instanceof Object_Type) {
            $fully_qualified_annotation_class = $type->get_class_name();
        }
        if ($fully_qualified_annotation_class === null) {
            return;
        }
        if ($values !== '') {
            $values = Strings::replace($values, self::STAR_COMMENT_REGEX);
            if ($php_doc_tag_node->value instanceof Doctrine_Tag_Value_Node) {
                $values = '(' . $values . ')';
                if ($php_doc_tag_node->value->description !== '') {
                    $values .= $php_doc_tag_node->value->description;
                }
            }
        }
        $generic_tag_value_node = new Generic_Tag_Value_Node($values);
        $start_and_end = $php_doc_tag_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        $generic_tag_value_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
        $spaceless_php_doc_tag_node = $this->create_spaceless_php_doc_tag_node('@' . $name, $generic_tag_value_node, $fully_qualified_annotation_class, $current_php_node);
        $this->attribute_mirrorer->mirror($php_doc_tag_node, $spaceless_php_doc_tag_node);
        $php_doc_node->children[$key] = $spaceless_php_doc_tag_node;
    }
    private function process_description_as_spaceless_php_doctag_node(Php_Doc_Node $php_doc_node, Php_Doc_Tag_Node $php_doc_tag_node, Node $current_php_node, int $key): void
    {
        if (!property_exists($php_doc_tag_node->value, 'description')) {
            return;
        }
        $description = (string) $php_doc_tag_node->value->description;
        if (strpos($description, "\n") === \false) {
            return;
        }
        $php_doc_text_node = new Php_Doc_Text_Node($description);
        $start_and_end = $php_doc_tag_node->value->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        if (!$start_and_end instanceof Start_And_End) {
            return;
        }
        $php_doc_text_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
        $spaceless_php_doc_tag_nodes = $this->resolve_fqn_annotation_spaceless_php_doc_tag_node($php_doc_text_node, $current_php_node);
        if ($spaceless_php_doc_tag_nodes === []) {
            return;
        }
        while (isset($php_doc_node->children[$key]) && $php_doc_node->children[$key] !== $php_doc_tag_node) {
            ++$key;
        }
        unset($php_doc_node->children[$key]);
        $class_node = new Php_Doc_Tag_Node($php_doc_tag_node->name, $php_doc_tag_node->value);
        $description = Strings::replace($description, self::LONG_ANNOTATION_REGEX, '');
        $description = (string) substr($description, 0, -7);
        $php_doc_tag_node->value->description = $description;
        $php_doc_node->children[$key] = $class_node;
        array_splice($php_doc_node->children, $key + 1, 0, $spaceless_php_doc_tag_nodes);
    }
    /**
     * This is closed block, e.g. {( ... )},
     * false on: {( ... )
     */
    private function is_closed_content(string $composed_content): bool
    {
        $composed_token_iterator = $this->token_iterator_factory->create($composed_content);
        $token_count = $composed_token_iterator->count();
        $open_bracket_count = 0;
        $close_bracket_count = 0;
        if ($composed_content === '') {
            return \true;
        }
        do {
            if ($composed_token_iterator->is_current_token_type(Lexer::TOKEN_OPEN_CURLY_BRACKET, Lexer::TOKEN_OPEN_PARENTHESES) || strpos($composed_token_iterator->current_token_value(), '{') !== \false || strpos($composed_token_iterator->current_token_value(), '(') !== \false) {
                ++$open_bracket_count;
            }
            if ($composed_token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET, Lexer::TOKEN_CLOSE_PARENTHESES) || strpos($composed_token_iterator->current_token_value(), '}') !== \false || strpos($composed_token_iterator->current_token_value(), ')') !== \false) {
                ++$close_bracket_count;
            }
            $composed_token_iterator->next();
        } while ($composed_token_iterator->current_position() < $token_count - 1);
        return $open_bracket_count === $close_bracket_count;
    }
    private function create_spaceless_php_doc_tag_node(string $tag_name, Generic_Tag_Value_Node $generic_tag_value_node, string $fully_qualified_annotation_class, Node $current_php_node): Spaceless_Php_Doc_Tag_Node
    {
        $former_start_end = $generic_tag_value_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        return $this->create_doctrine_spaceless_php_doc_tag_node($generic_tag_value_node->value, $tag_name, $fully_qualified_annotation_class, $former_start_end, $current_php_node);
    }
    private function create_doctrine_spaceless_php_doc_tag_node(string $annotation_content, string $tag_name, string $fully_qualified_annotation_class, Start_And_End $start_and_end, Node $current_php_node): Spaceless_Php_Doc_Tag_Node
    {
        $nested_token_iterator = $this->token_iterator_factory->create($annotation_content);
        // mimics doctrine behavior just in phpdoc-parser syntax :)
        // https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L742
        $values = $this->static_doctrine_annotation_parser->resolve_annotation_method_call($nested_token_iterator, $current_php_node);
        $identifier_type_node = new Identifier_Type_Node($tag_name);
        $identifier_type_node->set_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS, $fully_qualified_annotation_class);
        $doctrine_annotation_tag_value_node = new Doctrine_Annotation_Tag_Value_Node($identifier_type_node, $annotation_content, $values, Silent_Key_Map::CLASS_NAMES_TO_SILENT_KEYS[$fully_qualified_annotation_class] ?? null);
        $doctrine_annotation_tag_value_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
        return new Spaceless_Php_Doc_Tag_Node($tag_name, $doctrine_annotation_tag_value_node);
    }
    private function combine_start_and_end(\Php_Stan\Php_Doc_Parser\Ast\Node $start_php_doc_child_node, Php_Doc_Child_Node $end_php_doc_child_node): Start_And_End
    {
        /** @var StartAndEnd $currentStartAndEnd */
        $current_start_and_end = $start_php_doc_child_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        /** @var StartAndEnd $nextStartAndEnd */
        $next_start_and_end = $end_php_doc_child_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        return new Start_And_End($current_start_and_end->get_start(), $next_start_and_end->get_end());
    }
    /**
     * @return SpacelessPhpDocTagNode[]
     */
    private function resolve_fqn_annotation_spaceless_php_doc_tag_node(Php_Doc_Text_Node $php_doc_text_node, Node $current_php_node): array
    {
        $matches = Strings::match_all($php_doc_text_node->text, self::LONG_ANNOTATION_REGEX);
        $spaceless_php_doc_tag_nodes = [];
        foreach ($matches as $match) {
            $fully_qualified_annotation_class = $match['class_name'] ?? null;
            if ($fully_qualified_annotation_class === null) {
                continue;
            }
            $nested_annotation_open = explode('(', (string) $fully_qualified_annotation_class);
            $fully_qualified_annotation_class = $nested_annotation_open[0];
            $tag_name = '@\\' . $fully_qualified_annotation_class;
            $former_start_end = $php_doc_text_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
            $annotation_content = $this->resolve_annotation_content($match['annotation_content'] ?? '', $nested_annotation_open);
            $spaceless_php_doc_tag_nodes[] = $this->create_doctrine_spaceless_php_doc_tag_node($annotation_content, $tag_name, $fully_qualified_annotation_class, $former_start_end, $current_php_node);
        }
        return $spaceless_php_doc_tag_nodes;
    }
    /**
     * @param string[]|null[] $nestedAnnotationOpen
     */
    private function resolve_annotation_content(string $annotation_content, array $nested_annotation_open): string
    {
        if (!isset($nested_annotation_open[1])) {
            return $annotation_content;
        }
        $trimmed_nested_annotation_open = trim($nested_annotation_open[1]);
        if (substr_compare($trimmed_nested_annotation_open, '{', -strlen('{')) === 0) {
            return $annotation_content;
        }
        if ($trimmed_nested_annotation_open === '') {
            return $annotation_content;
        }
        return '("' . trim($trimmed_nested_annotation_open, '"\'') . '")';
    }
}