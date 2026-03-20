<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Attribute;
use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Template_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Attributes\Attribute_Mirrorer;
use Rector\Better_Php_Doc_Parser\Contract\Base_Php_Doc_Node_Visitor_Interface;
use Rector\Better_Php_Doc_Parser\Data_Provider\Current_Token_Iterator_Provider;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Spacing_Aware_Template_Tag_Value_Node;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor;
final class Template_Php_Doc_Node_Visitor extends Abstract_Php_Doc_Node_Visitor implements Base_Php_Doc_Node_Visitor_Interface
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
        if (!$node instanceof Template_Tag_Value_Node) {
            return null;
        }
        if ($node instanceof Spacing_Aware_Template_Tag_Value_Node) {
            return null;
        }
        $better_token_iterator = $this->current_token_iterator_provider->provide();
        $start_index = $node->get_attribute(Attribute::START_INDEX);
        $end_index = $node->get_attribute(Attribute::END_INDEX);
        if ($start_index === null || $end_index === null) {
            throw new Should_Not_Happen_Exception();
        }
        $prepositions = $this->resolve_preposition($better_token_iterator, $start_index, $end_index);
        $spacing_aware_template_tag_value_node = new Spacing_Aware_Template_Tag_Value_Node($node->name, $node->bound, $node->description, $prepositions);
        $this->attribute_mirrorer->mirror($node, $spacing_aware_template_tag_value_node);
        return $spacing_aware_template_tag_value_node;
    }
    private function resolve_preposition(Better_Token_Iterator $better_token_iterator, int $start_index, int $end_index): string
    {
        $partial_tokens = $better_token_iterator->partial_tokens($start_index, $end_index);
        foreach ($partial_tokens as $partial_token) {
            if ($partial_token[1] !== Lexer::TOKEN_IDENTIFIER) {
                continue;
            }
            if (!in_array($partial_token[0], ['as', 'of'], \true)) {
                continue;
            }
            return $partial_token[0];
        }
        return 'of';
    }
}