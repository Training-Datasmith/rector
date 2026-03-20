<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Array_Parser;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Plain_Value_Parser;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation\Curly_List_Node;
/**
 * Better version of doctrine/annotation - with phpdoc-parser and  static reflection
 * @see \Rector\Tests\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser\StaticDoctrineAnnotationParserTest
 */
final class Static_Doctrine_Annotation_Parser
{
    /**
     * @readonly
     */
    private Plain_Value_Parser $plain_value_parser;
    /**
     * @readonly
     */
    private Array_Parser $array_parser;
    public function __construct(Plain_Value_Parser $plain_value_parser, Array_Parser $array_parser)
    {
        $this->plain_value_parser = $plain_value_parser;
        $this->array_parser = $array_parser;
    }
    /**
     * mimics: https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L1024-L1041
     *
     * @return ArrayItemNode[]
     */
    public function resolve_annotation_method_call(Better_Token_Iterator $token_iterator, Node $current_php_node): array
    {
        if (!$token_iterator->is_current_token_type(Lexer::TOKEN_OPEN_PARENTHESES)) {
            return [];
        }
        $token_iterator->consume_token_type(Lexer::TOKEN_OPEN_PARENTHESES);
        // empty ()
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_PARENTHESES)) {
            return [];
        }
        return $this->resolve_annotation_values($token_iterator, $current_php_node);
    }
    /**
     * @api tests
     * @see https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L1215-L1224
     * @return CurlyListNode|string|array<mixed>|ConstExprNode|DoctrineAnnotationTagValueNode|StringNode
     */
    public function resolve_annotation_value(Better_Token_Iterator $token_iterator, Node $current_php_node)
    {
        // skips dummy tokens like newlines
        $token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
        // no assign
        if (!$token_iterator->is_next_token_type(Lexer::TOKEN_EQUAL)) {
            // 1. plain value - mimics https://github.com/doctrine/annotations/blob/0cb0cd2950a5c6cdbf22adbe2bfd5fd1ea68588f/lib/Doctrine/Common/Annotations/DocParser.php#L1234-L1282
            return $this->parse_value($token_iterator, $current_php_node);
        }
        // 2. assign key = value - mimics FieldAssignment() https://github.com/doctrine/annotations/blob/0cb0cd2950a5c6cdbf22adbe2bfd5fd1ea68588f/lib/Doctrine/Common/Annotations/DocParser.php#L1291-L1303
        /** @var int $key */
        $key = $this->parse_value($token_iterator, $current_php_node);
        $token_iterator->consume_token_type(Lexer::TOKEN_EQUAL);
        // mimics https://github.com/doctrine/annotations/blob/1.13.x/lib/Doctrine/Common/Annotations/DocParser.php#L1236-L1238
        $value = $this->parse_value($token_iterator, $current_php_node);
        return [
            // plain token value
            $key => $value,
        ];
    }
    /**
     * @see https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L1051-L1079
     *
     * @return ArrayItemNode[]
     */
    private function resolve_annotation_values(Better_Token_Iterator $token_iterator, Node $current_php_node): array
    {
        $values = [];
        $resolved_value = $this->resolve_annotation_value($token_iterator, $current_php_node);
        if (is_array($resolved_value)) {
            $values = array_merge($values, $resolved_value);
        } else {
            $values[] = $resolved_value;
        }
        while ($token_iterator->is_current_token_type(Lexer::TOKEN_COMMA)) {
            $token_iterator->next();
            // if is next item just closing brackets
            if ($token_iterator->is_next_token_type(Lexer::TOKEN_CLOSE_PARENTHESES)) {
                continue;
            }
            $nested_values = $this->resolve_annotation_value($token_iterator, $current_php_node);
            if (is_array($nested_values)) {
                $values = array_merge($values, $nested_values);
            } else {
                if ($token_iterator->is_current_token_type(Lexer::TOKEN_END)) {
                    break;
                }
                $values[] = $nested_values;
            }
        }
        return $this->array_parser->create_array_from_values($values);
    }
    /**
     * @return CurlyListNode|string|array<mixed>|ConstExprNode|DoctrineAnnotationTagValueNode|StringNode
     */
    private function parse_value(Better_Token_Iterator $token_iterator, Node $current_php_node)
    {
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_OPEN_CURLY_BRACKET)) {
            $items = $this->array_parser->parse_curly_array($token_iterator, $current_php_node);
            return new Curly_List_Node($items);
        }
        return $this->plain_value_parser->parse_value($token_iterator, $current_php_node);
    }
}