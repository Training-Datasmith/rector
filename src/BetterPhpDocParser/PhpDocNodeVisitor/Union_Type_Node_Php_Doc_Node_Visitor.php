<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Union_Type_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Attributes\Attribute_Mirrorer;
use Rector\Better_Php_Doc_Parser\Contract\Base_Php_Doc_Node_Visitor_Interface;
use Rector\Better_Php_Doc_Parser\Data_Provider\Current_Token_Iterator_Provider;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Better_Php_Doc_Parser\Value_Object\Start_And_End;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Union_Type_Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor;
final class Union_Type_Node_Php_Doc_Node_Visitor extends Abstract_Php_Doc_Node_Visitor implements Base_Php_Doc_Node_Visitor_Interface
{
    /**
     * @readonly
     */
    private Current_Token_Iterator_Provider $current_token_iterator_provider;
    /**
     * @readonly
     */
    private Attribute_Mirrorer $attribute_mirrorer;
    public function __construct(Current_Token_Iterator_Provider $current_token_iterator_provider, Attribute_Mirrorer $attribute_mirrorer)
    {
        $this->current_token_iterator_provider = $current_token_iterator_provider;
        $this->attribute_mirrorer = $attribute_mirrorer;
    }
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Union_Type_Node) {
            return null;
        }
        if ($node instanceof Brackets_Aware_Union_Type_Node) {
            return null;
        }
        $start_and_end = $this->resolve_start_and_end($node);
        if (!$start_and_end instanceof Start_And_End) {
            $first_key = array_key_first($node->types);
            $last_key = array_key_last($node->types);
            $start_and_end = new Start_And_End($node->types[$first_key]->get_attribute('startIndex'), $node->types[$last_key]->get_attribute('endIndex'));
        }
        $better_token_provider = $this->current_token_iterator_provider->provide();
        $is_wrapped_in_curly_brackets = $this->is_wrapped_in_curly_brackets($better_token_provider, $start_and_end);
        $brackets_aware_union_type_node = new Brackets_Aware_Union_Type_Node($node->types, $is_wrapped_in_curly_brackets);
        $this->attribute_mirrorer->mirror($node, $brackets_aware_union_type_node);
        return $brackets_aware_union_type_node;
    }
    private function is_wrapped_in_curly_brackets(Better_Token_Iterator $better_token_provider, Start_And_End $start_and_end): bool
    {
        $previous_position = $start_and_end->get_start() - 1;
        if ($better_token_provider->is_token_type_on_position(Lexer::TOKEN_OPEN_PARENTHESES, $previous_position)) {
            return \true;
        }
        // there is no + 1, as end is right at the next token
        return $better_token_provider->is_token_type_on_position(Lexer::TOKEN_CLOSE_PARENTHESES, $start_and_end->get_end());
    }
    private function resolve_start_and_end(Union_Type_Node $union_type_node): ?Start_And_End
    {
        $star_and_end = $union_type_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        if ($star_and_end instanceof Start_And_End) {
            return $star_and_end;
        }
        // unwrap with parent array type...
        $parent_node = $union_type_node->get_attribute(Php_Doc_Attribute_Key::PARENT);
        if (!$parent_node instanceof Node) {
            return null;
        }
        return $parent_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
    }
}