<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_Integer_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
/**
 * @see \Rector\Tests\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser\ArrayParserTest
 */
final class Array_Parser
{
    /**
     * @readonly
     */
    private \Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Plain_Value_Parser $plain_value_parser;
    public function __construct(\Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Plain_Value_Parser $plain_value_parser)
    {
        $this->plain_value_parser = $plain_value_parser;
    }
    /**
     * Mimics https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L1305-L1352
     *
     * @return ArrayItemNode[]
     */
    public function parse_curly_array(Better_Token_Iterator $token_iterator, Node $current_php_node): array
    {
        $values = [];
        // nothing
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET)) {
            return [];
        }
        $token_iterator->consume_token_type(Lexer::TOKEN_OPEN_CURLY_BRACKET);
        // If the array is empty, stop parsing and return.
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET)) {
            $token_iterator->consume_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET);
            return [];
        }
        // first item
        $values[] = $this->resolve_array_item($token_iterator, $current_php_node);
        // 2nd+ item
        while ($token_iterator->is_current_token_type(Lexer::TOKEN_COMMA)) {
            // optional trailing comma
            $token_iterator->consume_token_type(Lexer::TOKEN_COMMA);
            $token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
            if ($token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET)) {
                break;
            }
            $values[] = $this->resolve_array_item($token_iterator, $current_php_node);
            if ($token_iterator->is_next_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET)) {
                break;
            }
            // skip newlines
            $token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
        }
        $token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
        // special case for nested doctrine annotations
        if (!$token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_PARENTHESES)) {
            $token_iterator->try_consume_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET);
        }
        return $this->create_array_from_values($values);
    }
    /**
     * @param mixed[] $values
     * @return ArrayItemNode[]
     */
    public function create_array_from_values(array $values): array
    {
        $array_item_nodes = [];
        $natural_key = 0;
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                [$nested_key, $nested_value] = $value;
                if ($nested_key instanceof Const_Expr_Integer_Node) {
                    $nested_key = $nested_key->value;
                }
                // curly candidate?
                $array_item_nodes[] = $this->create_array_item_from_key_and_value($nested_key, $nested_value);
            } else {
                $array_item_nodes[] = $this->create_array_item_from_key_and_value($key !== $natural_key ? $key : null, $value);
            }
            ++$natural_key;
        }
        return $array_item_nodes;
    }
    /**
     * Mimics https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L1354-L1385
     * @return array<null|mixed, mixed>
     */
    private function resolve_array_item(Better_Token_Iterator $token_iterator, Node $current_php_node): array
    {
        // skip newlines
        $token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
        $key = null;
        // join "ClassName::CONSTANT_REFERENCE" to identifier
        if ($token_iterator->is_next_token_types([Lexer::TOKEN_DOUBLE_COLON])) {
            $key = $token_iterator->current_token_value();
            // "::"
            $token_iterator->next();
            $key .= $token_iterator->current_token_value();
            $token_iterator->consume_token_type(Lexer::TOKEN_DOUBLE_COLON);
            $key .= $token_iterator->current_token_value();
            $token_iterator->next();
        }
        $token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_CURLY_BRACKET, Lexer::TOKEN_COMMA)) {
            // it's a value, not a key
            return [null, $key];
        }
        if ($token_iterator->is_current_token_type(Lexer::TOKEN_EQUAL, Lexer::TOKEN_COLON) || $token_iterator->is_next_token_types([Lexer::TOKEN_EQUAL, Lexer::TOKEN_COLON])) {
            $token_iterator->try_consume_token_type(Lexer::TOKEN_EQUAL);
            $token_iterator->try_consume_token_type(Lexer::TOKEN_COLON);
            if ($key === null) {
                if ($token_iterator->is_next_token_type(Lexer::TOKEN_IDENTIFIER)) {
                    $key = $this->plain_value_parser->parse_value($token_iterator, $current_php_node);
                } else {
                    $token_iterator->try_consume_token_type(Lexer::TOKEN_COMMA);
                    $key = $this->plain_value_parser->parse_value($token_iterator, $current_php_node);
                }
            }
            $token_iterator->try_consume_token_type(Lexer::TOKEN_EQUAL);
            $token_iterator->try_consume_token_type(Lexer::TOKEN_COLON);
            return [$key, $this->plain_value_parser->parse_value($token_iterator, $current_php_node)];
        }
        return [$key, $this->plain_value_parser->parse_value($token_iterator, $current_php_node)];
    }
    /**
     * @return String_::KIND_SINGLE_QUOTED|String_::KIND_DOUBLE_QUOTED|null
     * @param mixed $val
     */
    private function resolve_quote_kind($val): ?int
    {
        if ($this->is_quoted_with($val, '"')) {
            return String_::KIND_DOUBLE_QUOTED;
        }
        if ($this->is_quoted_with($val, "'")) {
            return String_::KIND_SINGLE_QUOTED;
        }
        return null;
    }
    /**
     * @param mixed $rawKey
     * @param mixed $rawValue
     */
    private function create_array_item_from_key_and_value($raw_key, $raw_value): Array_Item_Node
    {
        $value_quote_kind = $this->resolve_quote_kind($raw_value);
        if (is_string($raw_value) && $value_quote_kind === String_::KIND_DOUBLE_QUOTED) {
            // give raw value
            $value = new String_Node((string) substr($raw_value, 1, strlen($raw_value) - 2));
        } elseif ($value_quote_kind === null && is_string($raw_value)) {
            $lower_raw_value = strtolower($raw_value);
            switch ($lower_raw_value) {
                case 'null':
                    $value = null;
                    break;
                case 'true':
                    $value = \true;
                    break;
                case 'false':
                    $value = \false;
                    break;
                default:
                    $value = $raw_value;
                    break;
            }
        } else {
            $value = $raw_value;
        }
        $key_quote_kind = $this->resolve_quote_kind($raw_key);
        if (is_string($raw_key) && $key_quote_kind === String_::KIND_DOUBLE_QUOTED) {
            // give raw value
            $key = new String_Node((string) substr($raw_key, 1, strlen($raw_key) - 2));
        } else {
            $key = $raw_key;
        }
        if (is_string($value) && $value_quote_kind === String_::KIND_SINGLE_QUOTED) {
            $value = trim($value, "'");
        }
        if ($key !== null) {
            return new Array_Item_Node($value, $key);
        }
        return new Array_Item_Node($value);
    }
    /**
     * @param mixed $value
     */
    private function is_quoted_with($value, string $quotes): bool
    {
        if (!is_string($value)) {
            return \false;
        }
        if (strncmp($value, $quotes, strlen($quotes)) !== 0) {
            return \false;
        }
        return substr_compare($value, $quotes, -strlen($quotes)) === 0;
    }
}