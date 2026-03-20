<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Info;

use Php_Parser\Comment\Doc;
use Php_Parser\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Annotation\Annotation_Naming;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Finder\Php_Doc_Node_By_Type_Finder;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Mapper;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Better_Php_Doc_Parser;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Better_Php_Doc_Parser\Value_Object\Start_And_End;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
final class Php_Doc_Info_Factory
{
    /**
     * @readonly
     */
    private Php_Doc_Node_Mapper $php_doc_node_mapper;
    /**
     * @readonly
     */
    private Lexer $lexer;
    /**
     * @readonly
     */
    private Better_Php_Doc_Parser $better_php_doc_parser;
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private Annotation_Naming $annotation_naming;
    /**
     * @readonly
     */
    private Php_Doc_Node_By_Type_Finder $php_doc_node_by_type_finder;
    /**
     * @var array<int, PhpDocInfo>
     */
    private array $php_doc_infos_by_object_id = [];
    public function __construct(Php_Doc_Node_Mapper $php_doc_node_mapper, Lexer $lexer, Better_Php_Doc_Parser $better_php_doc_parser, Static_Type_Mapper $static_type_mapper, Annotation_Naming $annotation_naming, Php_Doc_Node_By_Type_Finder $php_doc_node_by_type_finder)
    {
        $this->php_doc_node_mapper = $php_doc_node_mapper;
        $this->lexer = $lexer;
        $this->better_php_doc_parser = $better_php_doc_parser;
        $this->static_type_mapper = $static_type_mapper;
        $this->annotation_naming = $annotation_naming;
        $this->php_doc_node_by_type_finder = $php_doc_node_by_type_finder;
    }
    public function create_from_node_or_empty(Node $node): \Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info
    {
        // already added
        $php_doc_info = $node->get_attribute(Attribute_Key::PHP_DOC_INFO);
        if ($php_doc_info instanceof \Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info) {
            return $php_doc_info;
        }
        $php_doc_info = $this->create_from_node($node);
        if ($php_doc_info instanceof \Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info) {
            return $php_doc_info;
        }
        return $this->create_empty($node);
    }
    public function create_from_node(Node $node): ?\Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info
    {
        $object_id = spl_object_id($node);
        if (isset($this->php_doc_infos_by_object_id[$object_id])) {
            return $this->php_doc_infos_by_object_id[$object_id];
        }
        $doc_comment = $node->get_doc_comment();
        if (!$doc_comment instanceof Doc) {
            if ($node->get_comments() === []) {
                return null;
            }
            // create empty node
            $token_iterator = new Better_Token_Iterator([]);
            $php_doc_node = new Php_Doc_Node([]);
        } else {
            $tokens = $this->lexer->tokenize($doc_comment->get_text());
            $token_iterator = new Better_Token_Iterator($tokens);
            $php_doc_node = $this->better_php_doc_parser->parse_with_node($token_iterator, $node);
            $this->set_position_of_last_token($php_doc_node);
        }
        $php_doc_info = $this->create_from_php_doc_node($php_doc_node, $token_iterator, $node);
        $this->php_doc_infos_by_object_id[$object_id] = $php_doc_info;
        return $php_doc_info;
    }
    /**
     * @api downgrade
     */
    public function create_empty(Node $node): \Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info
    {
        $php_doc_node = new Php_Doc_Node([]);
        $php_doc_info = $this->create_from_php_doc_node($php_doc_node, new Better_Token_Iterator([]), $node);
        // multiline by default
        $php_doc_info->make_multi_lined();
        return $php_doc_info;
    }
    /**
     * Needed for printing
     */
    private function set_position_of_last_token(Php_Doc_Node $php_doc_node): void
    {
        if ($php_doc_node->children === []) {
            return;
        }
        $php_doc_child_nodes = $php_doc_node->children;
        $php_doc_child_node = array_pop($php_doc_child_nodes);
        $start_and_end = $php_doc_child_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        if ($start_and_end instanceof Start_And_End) {
            $php_doc_node->set_attribute(Php_Doc_Attribute_Key::LAST_PHP_DOC_TOKEN_POSITION, $start_and_end->get_end());
        }
    }
    private function create_from_php_doc_node(Php_Doc_Node $php_doc_node, Better_Token_Iterator $better_token_iterator, Node $node): \Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info
    {
        $this->php_doc_node_mapper->transform($php_doc_node, $better_token_iterator);
        $php_doc_info = new \Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info($php_doc_node, $better_token_iterator, $this->static_type_mapper, $node, $this->annotation_naming, $this->php_doc_node_by_type_finder);
        $node->set_attribute(Attribute_Key::PHP_DOC_INFO, $php_doc_info);
        return $php_doc_info;
    }
}