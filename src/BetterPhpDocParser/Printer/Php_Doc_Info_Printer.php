<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Printer;

use Php_Parser\Comment;
use Php_Parser\Node\Stmt\Inline_Html;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Param_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Child_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Property_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Return_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Throws_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Var_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor\Changed_Php_Doc_Node_Visitor;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Better_Php_Doc_Parser\Value_Object\Start_And_End;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Traverser;
use Rector\Util\String_Utils;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @see \Rector\Tests\BetterPhpDocParser\PhpDocInfo\PhpDocInfoPrinter\PhpDocInfoPrinterTest
 */
final class Php_Doc_Info_Printer
{
    /**
     * @readonly
     */
    private \Rector\Better_Php_Doc_Parser\Printer\Empty_Php_Doc_Detector $empty_php_doc_detector;
    /**
     * @readonly
     */
    private \Rector\Better_Php_Doc_Parser\Printer\Doc_Block_Inliner $doc_block_inliner;
    /**
     * @readonly
     */
    private \Rector\Better_Php_Doc_Parser\Printer\Remove_Nodes_Start_And_End_Resolver $remove_nodes_start_and_end_resolver;
    /**
     * @readonly
     */
    private Changed_Php_Doc_Node_Visitor $changed_php_doc_node_visitor;
    /**
     * @see https://regex101.com/r/Ab0Vey/1
     * @var string
     */
    private const CLOSING_DOCBLOCK_REGEX = '#\*\/(\s+)?$#';
    /**
     * @see https://regex101.com/r/5fJyws/1
     * @var string
     */
    private const CALLABLE_REGEX = '#callable(\s+)\(#';
    /**
     * @var string[]
     */
    private const DOCBLOCK_STARTS = ['//', '/**', '/*', '#'];
    /**
     * @var string Uses a hardcoded unix-newline since most codes use it (even on windows) - otherwise we would need to normalize newlines
     */
    private const NEWLINE_WITH_ASTERISK = "\n" . ' *';
    /**
     * @see https://regex101.com/r/ME5Fcn/1
     * @var string
     */
    private const NEW_LINE_WITH_SPACE_REGEX = "# (?<new_line>\r\n|\n)#";
    private int $token_count = 0;
    private int $current_token_position = 0;
    /**
     * @var mixed[]
     */
    private array $tokens = [];
    private ?Php_Doc_Info $php_doc_info = null;
    /**
     * @readonly
     */
    private Php_Doc_Node_Traverser $changed_php_doc_node_traverser;
    public function __construct(\Rector\Better_Php_Doc_Parser\Printer\Empty_Php_Doc_Detector $empty_php_doc_detector, \Rector\Better_Php_Doc_Parser\Printer\Doc_Block_Inliner $doc_block_inliner, \Rector\Better_Php_Doc_Parser\Printer\Remove_Nodes_Start_And_End_Resolver $remove_nodes_start_and_end_resolver, Changed_Php_Doc_Node_Visitor $changed_php_doc_node_visitor)
    {
        $this->empty_php_doc_detector = $empty_php_doc_detector;
        $this->doc_block_inliner = $doc_block_inliner;
        $this->remove_nodes_start_and_end_resolver = $remove_nodes_start_and_end_resolver;
        $this->changed_php_doc_node_visitor = $changed_php_doc_node_visitor;
        $changed_php_doc_node_traverser = new Php_Doc_Node_Traverser();
        $changed_php_doc_node_traverser->add_php_doc_node_visitor($this->changed_php_doc_node_visitor);
        $this->changed_php_doc_node_traverser = $changed_php_doc_node_traverser;
    }
    public function print_new(Php_Doc_Info $php_doc_info): string
    {
        $doc_content = (string) $php_doc_info->get_php_doc_node();
        if ($php_doc_info->is_single_line()) {
            return $this->doc_block_inliner->inline($doc_content);
        }
        if ($php_doc_info->get_node() instanceof Inline_Html) {
            return '<?php' . \PHP_EOL . $doc_content . \PHP_EOL . '?>';
        }
        return $doc_content;
    }
    /**
     * As in php-parser
     *
     * ref: https://github.com/nikic/PHP-Parser/issues/487#issuecomment-375986259
     * - Tokens[node.startPos .. subnode1.startPos]
     * - Print(subnode1)
     * - Tokens[subnode1.endPos .. subnode2.startPos]
     * - Print(subnode2)
     * - Tokens[subnode2.endPos .. node.endPos]
     */
    public function print_format_preserving(Php_Doc_Info $php_doc_info): string
    {
        if ($php_doc_info->get_tokens() === []) {
            // completely new one, just print string version of it
            if ($php_doc_info->get_php_doc_node()->children === []) {
                return '';
            }
            if ($php_doc_info->get_node() instanceof Inline_Html) {
                return '<?php' . \PHP_EOL . $php_doc_info->get_php_doc_node() . \PHP_EOL . '?>';
            }
            return (string) $php_doc_info->get_php_doc_node();
        }
        $php_doc_node = $php_doc_info->get_php_doc_node();
        $this->tokens = $php_doc_info->get_tokens();
        $this->token_count = $php_doc_info->get_token_count();
        $this->php_doc_info = $php_doc_info;
        $this->current_token_position = 0;
        $php_doc_string = $this->print_php_doc_node($php_doc_node);
        // hotfix of extra space with callable ()
        return Strings::replace($php_doc_string, self::CALLABLE_REGEX, 'callable(');
    }
    /**
     * @return Comment[]
     */
    public function print_to_comments(Php_Doc_Info $php_doc_info): array
    {
        $printed_php_doc_contents = $this->print_format_preserving($php_doc_info);
        return [new Comment($printed_php_doc_contents)];
    }
    private function get_current_php_doc_info(): Php_Doc_Info
    {
        if (!$this->php_doc_info instanceof Php_Doc_Info) {
            throw new Should_Not_Happen_Exception();
        }
        return $this->php_doc_info;
    }
    private function print_php_doc_node(Php_Doc_Node $php_doc_node): string
    {
        // no nodes were, so empty doc
        if ($this->empty_php_doc_detector->is_php_doc_node_empty($php_doc_node)) {
            return '';
        }
        $output = '';
        // node output
        $node_count = count($php_doc_node->children);
        foreach ($php_doc_node->children as $key => $php_doc_child_node) {
            $output .= $this->print_doc_child_node($php_doc_child_node, $key + 1, $node_count);
        }
        $output = $this->print_end($output);
        // fix missing start
        if (!$this->has_docblock_start($output) && $output !== '') {
            $output = '/**' . $output;
        }
        // fix missing end
        if (strncmp($output, '/**', strlen('/**')) === 0 && !String_Utils::is_match($output, self::CLOSING_DOCBLOCK_REGEX)) {
            $output .= ' */';
        }
        return Strings::replace($output, self::NEW_LINE_WITH_SPACE_REGEX, static fn(array $match): string => (string) $match['new_line']);
    }
    private function has_docblock_start(string $output): bool
    {
        foreach (self::DOCBLOCK_STARTS as $docblock_start) {
            if (strncmp($output, $docblock_start, strlen($docblock_start)) === 0) {
                return \true;
            }
        }
        return \false;
    }
    private function print_doc_child_node(Php_Doc_Child_Node $php_doc_child_node, int $key = 0, int $node_count = 0): string
    {
        $output = '';
        $should_reprint_child_node = $this->should_reprint($php_doc_child_node);
        if ($php_doc_child_node instanceof Php_Doc_Tag_Node && ($should_reprint_child_node && ($php_doc_child_node->value instanceof Param_Tag_Value_Node || $php_doc_child_node->value instanceof Throws_Tag_Value_Node || $php_doc_child_node->value instanceof Var_Tag_Value_Node || $php_doc_child_node->value instanceof Return_Tag_Value_Node || $php_doc_child_node->value instanceof Property_Tag_Value_Node))) {
            // the type has changed → reprint
            $php_doc_child_node_start_end = $php_doc_child_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
            // bump the last position of token after just printed node
            if ($php_doc_child_node_start_end instanceof Start_And_End) {
                $this->current_token_position = $php_doc_child_node_start_end->get_end();
            }
            return $this->standard_print_php_doc_child_node($php_doc_child_node);
        }
        /** @var StartAndEnd|null $startAndEnd */
        $start_and_end = $php_doc_child_node->get_attribute(Php_Doc_Attribute_Key::START_AND_END);
        if ($start_and_end instanceof Start_And_End && !$should_reprint_child_node) {
            $is_last_token = $node_count === $key;
            // correct previously changed node
            $this->correct_previously_reprinted_first_node($key, $start_and_end);
            $output = $this->add_tokens_from_to($output, $this->current_token_position, $start_and_end->get_end(), $is_last_token);
            $this->current_token_position = $start_and_end->get_end();
            return rtrim($output);
        }
        if ($start_and_end instanceof Start_And_End) {
            $this->current_token_position = $start_and_end->get_end();
        }
        $standard_printed_php_doc_child_node = $this->standard_print_php_doc_child_node($php_doc_child_node);
        return $output . $standard_printed_php_doc_child_node;
    }
    private function print_end(string $output): string
    {
        $last_token_position = $this->get_current_php_doc_info()->get_php_doc_node()->get_attribute(Php_Doc_Attribute_Key::LAST_PHP_DOC_TOKEN_POSITION);
        if ($last_token_position === null) {
            $last_token_position = $this->current_token_position;
        }
        if ($last_token_position === 0) {
            return $output . "\n */";
        }
        return $this->add_tokens_from_to($output, $last_token_position, $this->token_count, \true);
    }
    private function add_tokens_from_to(string $output, int $from, int $to, bool $should_skip_empty_lines_above): string
    {
        // skip removed nodes
        $position_jump_set = [];
        $removed_start_and_ends = $this->remove_nodes_start_and_end_resolver->resolve($this->get_current_php_doc_info()->get_original_php_doc_node(), $this->get_current_php_doc_info()->get_php_doc_node(), $this->tokens);
        foreach ($removed_start_and_ends as $removed_start_and_end) {
            $position_jump_set[$removed_start_and_end->get_start()] = $removed_start_and_end->get_end();
        }
        // include also space before, in case of inlined docs
        if (isset($this->tokens[$from - 1]) && $this->tokens[$from - 1][1] === Lexer::TOKEN_HORIZONTAL_WS) {
            --$from;
        }
        // skip extra empty lines above if this is the last one
        if ($should_skip_empty_lines_above && strpos((string) $this->tokens[$from][0], "\n") !== \false && strpos((string) $this->tokens[$from + 1][0], "\n") !== \false) {
            ++$from;
        }
        return $this->append_to_output($output, $from, $to, $position_jump_set);
    }
    /**
     * @param array<int, int> $positionJumpSet
     */
    private function append_to_output(string $output, int $from, int $to, array $position_jump_set): string
    {
        for ($i = $from; $i < $to; ++$i) {
            while (isset($position_jump_set[$i])) {
                $i = $position_jump_set[$i];
            }
            $output .= $this->tokens[$i][0] ?? '';
        }
        return $output;
    }
    private function correct_previously_reprinted_first_node(int $key, Start_And_End $start_and_end): void
    {
        if ($this->current_token_position !== 0) {
            return;
        }
        if ($key === 1) {
            return;
        }
        $start_token_position = $start_and_end->get_start();
        $tokens = $this->get_current_php_doc_info()->get_tokens();
        if (!isset($tokens[$start_token_position - 1])) {
            return;
        }
        $previous_token = $tokens[$start_token_position - 1];
        if ($previous_token[1] === Lexer::TOKEN_PHPDOC_EOL) {
            --$start_token_position;
        }
        $this->current_token_position = $start_token_position;
    }
    private function should_reprint(Php_Doc_Child_Node $php_doc_child_node): bool
    {
        $this->changed_php_doc_node_traverser->traverse($php_doc_child_node);
        return $this->changed_php_doc_node_visitor->has_changed();
    }
    private function standard_print_php_doc_child_node(Php_Doc_Child_Node $php_doc_child_node): string
    {
        $printed_node = (string) $php_doc_child_node;
        if ($this->get_current_php_doc_info()->is_single_line()) {
            return ' ' . $printed_node;
        }
        return self::NEWLINE_WITH_ASTERISK . ($printed_node === '' ? '' : ' ' . $printed_node);
    }
}