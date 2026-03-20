<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Parser;

use Override;
use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Generic_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Child_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Text_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Php_Stan\Php_Doc_Parser\Parser\Const_Expr_Parser;
use Php_Stan\Php_Doc_Parser\Parser\Parser_Exception;
use Php_Stan\Php_Doc_Parser\Parser\Php_Doc_Parser;
use Php_Stan\Php_Doc_Parser\Parser\Token_Iterator;
use Php_Stan\Php_Doc_Parser\Parser\Type_Parser;
use Php_Stan\Php_Doc_Parser\Parser_Config;
use Rector\Better_Php_Doc_Parser\Contract\Php_Doc_Parser\Php_Doc_Node_Decorator_Interface;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Token_Iterator_Factory;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Better_Php_Doc_Parser\Value_Object\Start_And_End;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Util\Reflection\Privates_Accessor;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @see \Rector\Tests\BetterPhpDocParser\PhpDocParser\TagValueNodeReprint\TagValueNodeReprintTest
 */
final class Better_Php_Doc_Parser extends Php_Doc_Parser
{
    /**
     * @readonly
     */
    private Token_Iterator_Factory $token_iterator_factory;
    /**
     * @var PhpDocNodeDecoratorInterface[]
     * @readonly
     */
    private array $php_doc_node_decorators;
    /**
     * @readonly
     */
    private Privates_Accessor $privates_accessor;
    /**
     * @see https://regex101.com/r/JDzr0c/1
     * @var string
     */
    private const NEW_LINE_REGEX = "#(?<new_line>\r\n|\n)#";
    /**
     * @see https://regex101.com/r/JOKSmr/5
     * @var string
     */
    private const MULTI_NEW_LINES_REGEX = '#(?<new_line>\r\n|\n){2,}#';
    /**
     * @param PhpDocNodeDecoratorInterface[] $phpDocNodeDecorators
     */
    public function __construct(Parser_Config $parser_config, Type_Parser $type_parser, Const_Expr_Parser $const_expr_parser, Token_Iterator_Factory $token_iterator_factory, array $php_doc_node_decorators, Privates_Accessor $privates_accessor)
    {
        $this->token_iterator_factory = $token_iterator_factory;
        $this->php_doc_node_decorators = $php_doc_node_decorators;
        $this->privates_accessor = $privates_accessor;
        parent::__construct(
            // ParserConfig
            $parser_config,
            // TypeParser
            $type_parser,
            // ConstExprParser
            $const_expr_parser
        );
    }
    public function parse_with_node(Better_Token_Iterator $better_token_iterator, Node $node): Php_Doc_Node
    {
        $better_token_iterator->consume_token_type(Lexer::TOKEN_OPEN_PHPDOC);
        $better_token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL);
        $children = [];
        if (!$better_token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_PHPDOC)) {
            $children[] = $this->parse_child_and_store_its_positions($better_token_iterator);
            while ($better_token_iterator->try_consume_token_type(Lexer::TOKEN_PHPDOC_EOL) && !$better_token_iterator->is_current_token_type(Lexer::TOKEN_CLOSE_PHPDOC)) {
                $children[] = $this->parse_child_and_store_its_positions($better_token_iterator);
            }
        }
        // might be in the middle of annotations
        $better_token_iterator->try_consume_token_type(Lexer::TOKEN_CLOSE_PHPDOC);
        $php_doc_node = new Php_Doc_Node($children);
        foreach ($this->php_doc_node_decorators as $php_doc_node_decorator) {
            $php_doc_node_decorator->decorate($php_doc_node, $node);
        }
        return $php_doc_node;
    }
    #[Override]
    public function parse_tag(Token_Iterator $token_iterator): Php_Doc_Tag_Node
    {
        // replace generic nodes with DoctrineAnnotations
        if (!$token_iterator instanceof Better_Token_Iterator) {
            throw new Should_Not_Happen_Exception();
        }
        $tag = $this->resolve_tag($token_iterator);
        $php_doc_tag_value_node = $this->parse_tag_value($token_iterator, $tag);
        return new Php_Doc_Tag_Node($tag, $php_doc_tag_value_node);
    }
    /**
     * @param BetterTokenIterator $tokenIterator
     */
    #[Override]
    public function parse_tag_value(Token_Iterator $token_iterator, string $tag): Php_Doc_Tag_Value_Node
    {
        $is_preceded_by_horizontal_whitespace = $token_iterator->is_preceded_by_horizontal_whitespace();
        $start_position = $token_iterator->current_position();
        $php_doc_tag_value_node = parent::parse_tag_value($token_iterator, $tag);
        $end_position = $token_iterator->current_position();
        if ($is_preceded_by_horizontal_whitespace && property_exists($php_doc_tag_value_node, 'description')) {
            $php_doc_tag_value_node->description = Strings::replace((string) $php_doc_tag_value_node->description, self::NEW_LINE_REGEX, static fn(array $match): string => $match['new_line'] . ' * ');
        }
        $start_and_end = new Start_And_End($start_position, $end_position);
        $php_doc_tag_value_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
        if ($php_doc_tag_value_node instanceof Generic_Tag_Value_Node) {
            $php_doc_tag_value_node->value = Strings::replace($php_doc_tag_value_node->value, self::MULTI_NEW_LINES_REGEX, static fn(array $match): string => (string) $match['new_line']);
        }
        return $php_doc_tag_value_node;
    }
    /**
     * @return PhpDocTextNode|PhpDocTagNode
     */
    private function parse_child_and_store_its_positions(Token_Iterator $token_iterator): Php_Doc_Child_Node
    {
        $better_token_iterator = $this->token_iterator_factory->create_from_token_iterator($token_iterator);
        $start_position = $better_token_iterator->current_position();
        try {
            /** @var PhpDocTextNode|PhpDocTagNode $phpDocNode */
            $php_doc_node = $this->privates_accessor->call_private_method($this, 'parseChild', [$better_token_iterator]);
        } catch (Parser_Exception $exception) {
            $php_doc_node = new Php_Doc_Text_Node('');
        }
        $end_position = $better_token_iterator->current_position();
        $start_and_end = new Start_And_End($start_position, $end_position);
        $php_doc_node->set_attribute(Php_Doc_Attribute_Key::START_AND_END, $start_and_end);
        return $php_doc_node;
    }
    private function resolve_tag(Better_Token_Iterator $token_iterator): string
    {
        $tag = $token_iterator->current_token_value();
        $token_iterator->next();
        // there is a space → stop
        if ($token_iterator->is_preceded_by_horizontal_whitespace()) {
            return $tag;
        }
        // is not e.g "@var "
        // join tags like "@ORM\Column" etc.
        if (!$token_iterator->is_current_token_type(Lexer::TOKEN_IDENTIFIER)) {
            return $tag;
        }
        // @todo use joinUntil("(")?
        $tag .= $token_iterator->current_token_value();
        $token_iterator->next();
        return $tag;
    }
}