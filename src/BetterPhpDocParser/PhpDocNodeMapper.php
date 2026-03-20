<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser;

use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Rector\Better_Php_Doc_Parser\Contract\Base_Php_Doc_Node_Visitor_Interface;
use Rector\Better_Php_Doc_Parser\Data_Provider\Current_Token_Iterator_Provider;
use Rector\Better_Php_Doc_Parser\Value_Object\Parser\Better_Token_Iterator;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Cloning_Php_Doc_Node_Visitor;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Parent_Connecting_Php_Doc_Node_Visitor;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\BetterPhpDocParser\PhpDocNodeMapperTest
 */
final class Php_Doc_Node_Mapper
{
    /**
     * @readonly
     */
    private Current_Token_Iterator_Provider $current_token_iterator_provider;
    /**
     * @var BasePhpDocNodeVisitorInterface[]
     * @readonly
     */
    private array $php_doc_node_visitors;
    /**
     * @readonly
     */
    private Php_Doc_Node_Traverser $php_doc_node_traverser;
    /**
     * @param BasePhpDocNodeVisitorInterface[] $phpDocNodeVisitors
     */
    public function __construct(Current_Token_Iterator_Provider $current_token_iterator_provider, Parent_Connecting_Php_Doc_Node_Visitor $parent_connecting_php_doc_node_visitor, Cloning_Php_Doc_Node_Visitor $cloning_php_doc_node_visitor, array $php_doc_node_visitors)
    {
        $this->current_token_iterator_provider = $current_token_iterator_provider;
        $this->php_doc_node_visitors = $php_doc_node_visitors;
        Assert::not_empty($php_doc_node_visitors);
        $this->php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $this->php_doc_node_traverser->add_php_doc_node_visitor($parent_connecting_php_doc_node_visitor);
        $this->php_doc_node_traverser->add_php_doc_node_visitor($cloning_php_doc_node_visitor);
        foreach ($this->php_doc_node_visitors as $php_doc_node_visitor) {
            $this->php_doc_node_traverser->add_php_doc_node_visitor($php_doc_node_visitor);
        }
    }
    public function transform(Php_Doc_Node $php_doc_node, Better_Token_Iterator $better_token_iterator): void
    {
        $this->current_token_iterator_provider->set_better_token_iterator($better_token_iterator);
        $this->php_doc_node_traverser->traverse($php_doc_node);
    }
}