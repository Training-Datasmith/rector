<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_False_Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_Integer_Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_True_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Class_Annotation_Matcher;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
final class Plain_Value_Parser
{
    /**
     * @readonly
     */
    private Class_Annotation_Matcher $class_annotation_matcher;
    private Static_Doctrine_Annotation_Parser $static_doctrine_annotation_parser;
    private \Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Array_Parser $array_parser;
    public function __construct(Class_Annotation_Matcher $class_annotation_matcher)
    {
        $this->class_annotation_matcher = $class_annotation_matcher;
    }
    public function autowire(Static_Doctrine_Annotation_Parser $static_doctrine_annotation_parser, \Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Array_Parser $array_parser): void
    {
        $this->static_doctrine_annotation_parser = $static_doctrine_annotation_parser;
        $this->array_parser = $array_parser;
    }
    /**
     * @return string|mixed[]|ConstExprNode|DoctrineAnnotationTagValueNode|StringNode
     */
    public function parse_value(Better_Token_Iterator $token_iterator, Node $current_php_node)
    {
        $current_token_value = $token_iterator->current_token_value();
        // temporary hackaround multi-line doctrine annotations
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_END)) {
            return $current_token_value;
        }
        // consume the token
        $is_open_curly_array = $token_iterator->is_current_token_type(Lexer::TOKEN_OPEN_CURLY_BRACKET);
        if ($is_open_curly_array) {
            return $this->array_parser->parse_curly_array($token_iterator, $current_php_node);
        }
        $token_iterator->next();
        // normalize value
        $const_expr_node = $this->match_constant_value($current_token_value);
        if ($const_expr_node instanceof Const_Expr_Node) {
            return $const_expr_node;
        }
        $current_token_value = $this->parse_string_value($token_iterator, $current_token_value);
        // nested entity!, supported in attribute since PHP 8.1
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_OPEN_PARENTHESES)) {
            return $this->parse_nested_doctrine_annotation_tag_value_node($current_token_value, $token_iterator, $current_php_node);
        }
        $start = $token_iterator->current_position();
        // from "quote to quote"
        if ($current_token_value === '"') {
            do {
                $token_iterator->next();
            } while (strpos($token_iterator->current_token_value(), '"') === \false);
        }
        $end = $token_iterator->current_position();
        if ($start + 1 < $end) {
            return new String_Node($token_iterator->print_from_to($start, $end));
        }
        return $current_token_value;
    }
    private function parse_string_value(Better_Token_Iterator $token_iterator, string $current_token_value): string
    {
        if (strncmp($current_token_value, '"', strlen('"')) === 0 && substr_compare($current_token_value, '"', -strlen('"')) !== 0) {
            $current_token_value = $this->parse_multiline_or_white_spaced_string($token_iterator, $current_token_value);
        } else {
            while ($token_iterator->is_current_token_type(Lexer::TOKEN_DOUBLE_COLON) || $token_iterator->is_current_token_type(Lexer::TOKEN_IDENTIFIER)) {
                $current_token_value .= $token_iterator->current_token_value();
                $token_iterator->next();
            }
        }
        return $current_token_value;
    }
    private function parse_multiline_or_white_spaced_string(Better_Token_Iterator $token_iterator, string $current_token_value): string
    {
        while (strncmp($current_token_value, '"', strlen('"')) === 0 && substr_compare($current_token_value, '"', -strlen('"')) !== 0) {
            if (!$token_iterator->is_current_token_type(Lexer::TOKEN_PHPDOC_EOL)) {
                $current_token_value .= ' ';
            }
            if (strncmp($current_token_value, '"', strlen('"')) === 0 && strpos($token_iterator->current_token_value(), '"') !== \false && $current_token_value !== $token_iterator->current_token_value()) {
                //starts with '"' and current token contains '"', should be the end
                $current_token_value .= substr($token_iterator->current_token_value(), 0, strpos($token_iterator->current_token_value(), '"') + 1);
                $token_iterator->next();
                break;
            }
            $current_token_value .= $token_iterator->current_token_value();
            $token_iterator->next();
        }
        if (strncmp($current_token_value, '"', strlen('"')) === 0 && substr_compare($current_token_value, '"', -strlen('"')) === 0) {
            return trim(str_replace('"', '', $current_token_value));
        }
        return $current_token_value;
    }
    private function parse_nested_doctrine_annotation_tag_value_node(string $current_token_value, Better_Token_Iterator $token_iterator, Node $current_php_node): Doctrine_Annotation_Tag_Value_Node
    {
        // @todo
        $annotation_short_name = $current_token_value;
        $values = $this->static_doctrine_annotation_parser->resolve_annotation_method_call($token_iterator, $current_php_node);
        $fully_qualified_annotation_class = $this->class_annotation_matcher->resolve_tag_fully_qualified_name($annotation_short_name, $current_php_node);
        // keep the last ")"
        $token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
        if ($token_iterator->current_token_value() === ')') {
            $token_iterator->consume_token_type(Lexer::TOKEN_CLOSE_PARENTHESES);
        }
        // keep original name to differentiate between short and FQN class
        $identifier_type_node = new Identifier_Type_Node($annotation_short_name);
        $identifier_type_node->set_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS, $fully_qualified_annotation_class);
        return new Doctrine_Annotation_Tag_Value_Node($identifier_type_node, $annotation_short_name, $values);
    }
    private function match_constant_value(string $current_token_value): ?\Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_Node
    {
        if (strtolower($current_token_value) === 'false') {
            return new Const_Expr_False_Node();
        }
        if (strtolower($current_token_value) === 'true') {
            return new Const_Expr_True_Node();
        }
        if (!is_numeric($current_token_value)) {
            return null;
        }
        if ((string) (int) $current_token_value !== $current_token_value) {
            return null;
        }
        return new Const_Expr_Integer_Node($current_token_value);
    }
}