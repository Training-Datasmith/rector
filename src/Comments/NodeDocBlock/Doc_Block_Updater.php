<?php

declare (strict_types=1);
namespace Rector\Comments\Node_Doc_Block;

use Php_Parser\Comment;
use Php_Parser\Comment\Doc;
use Php_Parser\Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Printer\Php_Doc_Info_Printer;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Doc_Block_Updater
{
    /**
     * @readonly
     */
    private Php_Doc_Info_Printer $php_doc_info_printer;
    public function __construct(Php_Doc_Info_Printer $php_doc_info_printer)
    {
        $this->php_doc_info_printer = $php_doc_info_printer;
    }
    public function update_refactored_node_with_php_doc_info(Node $node): void
    {
        // nothing to change? don't save it
        $php_doc_info = $node->get_attribute(Attribute_Key::PHP_DOC_INFO);
        if (!$php_doc_info instanceof Php_Doc_Info) {
            return;
        }
        $php_doc_node = $php_doc_info->get_php_doc_node();
        if ($php_doc_node->children === []) {
            $this->set_comments_attribute($node);
            return;
        }
        $printed_php_doc = $this->print_php_doc_info_to_string($php_doc_info);
        $node->set_doc_comment(new Doc($printed_php_doc));
        if ($printed_php_doc === '') {
            $this->clear_empty_doc($node);
        }
    }
    private function set_comments_attribute(Node $node): void
    {
        $doc_comment = $node->get_doc_comment();
        $doc_comment_text = $doc_comment instanceof Doc ? $doc_comment->get_text() : null;
        $comments = array_filter($node->get_comments(), static function (Comment $comment) use ($doc_comment_text): bool {
            if (!$comment instanceof Doc) {
                return \true;
            }
            // remove only the docblock that belongs to the node itself;
            // keep other preceding docblocks (possible with multiple @var docblocks before a statement)
            if ($doc_comment_text !== null && $comment->get_text() === $doc_comment_text) {
                return \false;
            }
            return \true;
        });
        $node->set_attribute(Attribute_Key::COMMENTS, array_values($comments));
    }
    private function clear_empty_doc(Node $node): void
    {
        $comments = array_filter($node->get_comments(), static fn(Comment $comment): bool => !$comment instanceof Doc || $comment->get_text() !== '');
        $node->set_attribute(Attribute_Key::COMMENTS, array_values($comments));
    }
    private function print_php_doc_info_to_string(Php_Doc_Info $php_doc_info): string
    {
        if ($php_doc_info->is_new_node()) {
            return $this->php_doc_info_printer->print_new($php_doc_info);
        }
        return $this->php_doc_info_printer->print_format_preserving($php_doc_info);
    }
}