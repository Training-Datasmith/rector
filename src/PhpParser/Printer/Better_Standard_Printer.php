<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Printer;

use Override;
use Php_Parser\Comment;
use Php_Parser\Internal\Token_Stream;
use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Arrow_Function;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Binary_Op\Pipe;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Instanceof_;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Ternary;
use Php_Parser\Node\Expr\Yield_;
use Php_Parser\Node\Interpolated_String_Part;
use Php_Parser\Node\Scalar\Interpolated_String;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Declare_;
use Php_Parser\Node\Stmt\Inline_Html;
use Php_Parser\Node\Stmt\Nop;
use Php_Parser\Pretty_Printer\Standard;
use Php_Parser\Token;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Node_Analyzer\Expr_Analyzer;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Node\File_Node;
use Rector\Util\New_Line_Splitter;
use Rector\Util\Reflection\Privates_Accessor;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @see \Rector\Tests\PhpParser\Printer\BetterStandardPrinterTest
 *
 * @property array<string, array{string, bool, string, null}> $insertionMap
 */
final class Better_Standard_Printer extends Standard
{
    /**
     * @readonly
     */
    private Expr_Analyzer $expr_analyzer;
    /**
     * @readonly
     */
    private Privates_Accessor $privates_accessor;
    /**
     * Remove extra spaces before new Nop_ nodes
     * @see https://regex101.com/r/iSvroO/1
     * @var string
     */
    private const EXTRA_SPACE_BEFORE_NOP_REGEX = '#^[ \t]+$#m';
    public function __construct(Expr_Analyzer $expr_analyzer, Privates_Accessor $privates_accessor)
    {
        $this->expr_analyzer = $expr_analyzer;
        $this->privates_accessor = $privates_accessor;
        parent::__construct();
    }
    /**
     * @param Node[] $stmts
     * @param Node[] $origStmts
     * @param mixed[] $origTokens
     */
    #[Override]
    public function print_format_preserving(array $stmts, array $orig_stmts, array $orig_tokens): string
    {
        $new_stmts = $this->unwrap_file_node($stmts);
        $orig_stmts = $this->unwrap_file_node($orig_stmts);
        $content = parent::print_format_preserving($new_stmts, $orig_stmts, $orig_tokens);
        // add new line in case of added stmts
        if (count($new_stmts) !== count($orig_stmts) && substr_compare($content, "\n", -strlen("\n")) !== 0) {
            $content .= $this->nl;
        }
        return $content;
    }
    /**
     * @param Node|Node[]|null $node
     */
    public function print($node): string
    {
        if ($node === null) {
            $node = [];
        }
        if (!is_array($node)) {
            $node = [$node];
        }
        $node = $this->unwrap_file_node($node);
        return $this->pretty_print($node);
    }
    /**
     * @param Node[] $stmts
     */
    #[Override]
    public function pretty_print_file(array $stmts): string
    {
        // to keep indexes from 0
        $stmts = array_values($stmts);
        return parent::pretty_print_file($stmts) . \PHP_EOL;
    }
    /**
     * Use for standalone InterpolatedStringPart printing, that is not support by php-parser natively.
     * Used e.g. in \Rector\PhpParser\Comparing\NodeComparator::printWithoutComments
     */
    protected function p_interpolated_string_part(Interpolated_String_Part $interpolated_string_part): string
    {
        return $interpolated_string_part->value;
    }
    #[Override]
    protected function p(Node $node, int $precedence = self::MAX_PRECEDENCE, int $lhs_precedence = self::MAX_PRECEDENCE, bool $parent_format_preserved = \false): string
    {
        $this->wrap_binary_op_with_brackets($node);
        $content = parent::p($node, $precedence, $lhs_precedence, $parent_format_preserved);
        // remove once its fixed in php-parser, https://github.com/nikic/PHP-Parser/pull/1126
        if ($node instanceof Call_Like) {
            $this->clean_variadic_place_holder_trailing_comma($node);
        }
        if ($node->get_attribute(Attribute_Key::WRAPPED_IN_PARENTHESES)) {
            return '(' . $content . ')';
        }
        return $content;
    }
    protected function p_stmt_file_node(File_Node $file_node): string
    {
        return $this->p_stmts($file_node->stmts);
    }
    #[Override]
    protected function p_expr_arrow_function(Arrow_Function $arrow_function, int $precedence, int $lhs_precedence): string
    {
        if (!$arrow_function->has_attribute(Attribute_Key::COMMENTS)) {
            return parent::p_expr_arrow_function($arrow_function, $precedence, $lhs_precedence);
        }
        $expr = $arrow_function->expr;
        /** @var Comment[] $comments */
        $comments = $expr->get_attribute(Attribute_Key::COMMENTS) ?? [];
        if ($comments === []) {
            return parent::p_expr_arrow_function($arrow_function, $precedence, $lhs_precedence);
        }
        $is_max_precedence = $precedence === self::MAX_PRECEDENCE;
        $is_new_line_and_indent = $arrow_function->get_attribute(Attribute_Key::IS_ARG_VALUE) === \true;
        $indent = $this->resolve_indent_spaces($is_max_precedence);
        $text = $is_max_precedence ? '' : "\n" . $indent;
        if ($is_new_line_and_indent) {
            $indent = $this->resolve_indent_spaces();
            $text = "\n" . $indent;
        }
        foreach ($comments as $key => $comment) {
            $comment_text = $key > 0 ? $indent . $comment->get_text() : $comment->get_text();
            $text .= $comment_text . "\n";
        }
        return $text . $indent . parent::p_expr_arrow_function($arrow_function, $precedence, $lhs_precedence);
    }
    /**
     * This allows to use both spaces and tabs vs. original space-only
     */
    #[Override]
    protected function set_indent_level(int $level): void
    {
        $level = max($level, 0);
        $this->indent_level = $level;
        $this->nl = "\n" . str_repeat($this->get_indent_character(), $level);
    }
    /**
     * This allows to use both spaces and tabs vs. original space-only
     */
    #[Override]
    protected function indent(): void
    {
        $indent_size = Simple_Parameter_Provider::provide_int_parameter(Option::INDENT_SIZE);
        $this->indent_level += $indent_size;
        $this->nl .= str_repeat($this->get_indent_character(), $indent_size);
    }
    /**
     * This allows to use both spaces and tabs vs. original space-only
     */
    #[Override]
    protected function outdent(): void
    {
        if ($this->get_indent_character() === ' ') {
            $indent_size = Simple_Parameter_Provider::provide_int_parameter(Option::INDENT_SIZE);
            assert($this->indent_level >= $indent_size);
            $this->indent_level -= $indent_size;
        } else {
            // - 1 tab
            assert($this->indent_level >= 1);
            --$this->indent_level;
        }
        $this->nl = "\n" . str_repeat($this->get_indent_character(), $this->indent_level);
    }
    /**
     * @param mixed[] $nodes
     * @param mixed[] $origNodes
     */
    #[Override]
    protected function p_array(array $nodes, array $orig_nodes, int &$pos, int $indent_adjustment, string $parent_node_class, string $sub_node_name, ?int $fixup): ?string
    {
        // reindex positions for printer
        $nodes = array_values($nodes);
        $content = parent::p_array($nodes, $orig_nodes, $pos, $indent_adjustment, $parent_node_class, $sub_node_name, $fixup);
        if ($content === null) {
            return $content;
        }
        if (!$this->contains_nop($nodes)) {
            return $content;
        }
        return Strings::replace($content, self::EXTRA_SPACE_BEFORE_NOP_REGEX);
    }
    /**
     * Do not add "()" on Expressions
     * @see https://github.com/rectorphp/rector/pull/401#discussion_r181487199
     */
    #[Override]
    protected function p_expr_yield(Yield_ $yield, int $precedence, int $lhs_precedence): string
    {
        if (!$yield->value instanceof Expr) {
            return 'yield';
        }
        // brackets are needed only in case of assign, @see https://www.php.net/manual/en/language.generators.syntax.php
        $should_add_brackets = (bool) $yield->get_attribute(Attribute_Key::IS_ASSIGNED_TO);
        return sprintf('%syield %s%s%s', $should_add_brackets ? '(' : '', $yield->key instanceof Expr ? $this->p($yield->key) . ' => ' : '', $this->p($yield->value), $should_add_brackets ? ')' : '');
    }
    /**
     * Print new lined array items when newlined_array_print is set to true
     */
    #[Override]
    protected function p_expr_array(Array_ $array): string
    {
        if ($array->get_attribute(Attribute_Key::NEWLINED_ARRAY_PRINT) === \true) {
            $printed_array = '[';
            $printed_array .= $this->p_comma_separated_multiline($array->items, \true);
            return $printed_array . ($this->nl . ']');
        }
        return parent::p_expr_array($array);
    }
    #[Override]
    protected function p_expr_binary_op_pipe(Pipe $node, int $precedence, int $lhs_precedence): string
    {
        return $this->p_infix_op(Pipe::class, $node->left, "\n" . $this->resolve_indent_spaces() . '|> ', $node->right, $precedence, $lhs_precedence);
    }
    /**
     * Fixes escaping of regular patterns
     */
    #[Override]
    protected function p_scalar_string(String_ $string): string
    {
        if ($string->get_attribute(Attribute_Key::DOC_INDENTATION) === '__REMOVED__') {
            $content = parent::p_scalar_string($string);
            return $this->clean_start_indentation_on_heredoc_now_doc($content);
        }
        $is_regular_pattern = (bool) $string->get_attribute(Attribute_Key::IS_REGULAR_PATTERN, \false);
        if (!$is_regular_pattern) {
            return parent::p_scalar_string($string);
        }
        $kind = $string->get_attribute(Attribute_Key::KIND, String_::KIND_SINGLE_QUOTED);
        if ($kind === String_::KIND_DOUBLE_QUOTED) {
            return '"' . $string->value . '"';
        }
        if ($kind === String_::KIND_SINGLE_QUOTED) {
            return "'" . $string->value . "'";
        }
        return parent::p_scalar_string($string);
    }
    /**
     * It remove all spaces extra to parent
     */
    #[Override]
    protected function p_stmt_declare(Declare_ $declare): string
    {
        $declare_string = parent::p_stmt_declare($declare);
        return Strings::replace($declare_string, '#\s+#');
    }
    #[Override]
    protected function p_expr_ternary(Ternary $ternary, int $precedence, int $lhs_precedence): string
    {
        $kind = $ternary->get_attribute(Attribute_Key::KIND);
        if ($kind === Attribute_Key::WRAPPED_IN_PARENTHESES) {
            $p_expr_ternary = parent::p_expr_ternary($ternary, $precedence, $lhs_precedence);
            return '(' . $p_expr_ternary . ')';
        }
        return parent::p_expr_ternary($ternary, $precedence, $lhs_precedence);
    }
    /**
     * Used in rector-downgrade-php
     */
    #[Override]
    protected function p_scalar_interpolated_string(Interpolated_String $interpolated_string): string
    {
        $content = parent::p_scalar_interpolated_string($interpolated_string);
        if ($interpolated_string->get_attribute(Attribute_Key::DOC_INDENTATION) === '__REMOVED__') {
            return $this->clean_start_indentation_on_heredoc_now_doc($content);
        }
        return $content;
    }
    #[Override]
    protected function p_expr_method_call(Method_Call $method_call): string
    {
        if (!$method_call->var instanceof Call_Like) {
            return parent::p_expr_method_call($method_call);
        }
        if (Simple_Parameter_Provider::provide_bool_parameter(Option::NEW_LINE_ON_FLUENT_CALL) === \false) {
            return parent::p_expr_method_call($method_call);
        }
        foreach ($method_call->args as $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }
            $arg->value->set_attribute(Attribute_Key::ORIGINAL_NODE, null);
        }
        return $this->p_dereference_lhs($method_call->var) . "\n" . $this->resolve_indent_spaces() . '->' . $this->p_object_property($method_call->name) . '(' . $this->p_maybe_multiline($method_call->args) . ')';
    }
    #[Override]
    protected function p_infix_op(string $class, Node $left_node, string $operator_string, Node $right_node, int $precedence, int $lhs_precedence): string
    {
        $this->wrap_assign($left_node, $right_node);
        return parent::p_infix_op($class, $left_node, $operator_string, $right_node, $precedence, $lhs_precedence);
    }
    #[Override]
    protected function p_expr_instanceof(Instanceof_ $instanceof, int $precedence, int $lhs_precedence): string
    {
        $this->wrap_assign($instanceof->expr, $instanceof->class);
        return parent::p_expr_instanceof($instanceof, $precedence, $lhs_precedence);
    }
    /**
     * @todo remove once https://github.com/nikic/PHP-Parser/pull/1125 is merged and released
     */
    private function clean_variadic_place_holder_trailing_comma(Call_Like $call_like): void
    {
        $original_node = $call_like->get_attribute(Attribute_Key::ORIGINAL_NODE);
        if (!$original_node instanceof Call_Like) {
            return;
        }
        if ($original_node->is_first_class_callable()) {
            return;
        }
        if (!$call_like->is_first_class_callable()) {
            return;
        }
        if (!$this->orig_tokens instanceof Token_Stream) {
            return;
        }
        /** @var Token[] $tokens */
        $tokens = $this->privates_accessor->get_private_property($this->orig_tokens, 'tokens');
        $iteration = 1;
        while (isset($tokens[$call_like->get_end_token_pos() - $iteration])) {
            $text = trim((string) $tokens[$call_like->get_end_token_pos() - $iteration]->text);
            if (in_array($text, [')', ''], \true)) {
                ++$iteration;
                continue;
            }
            if ($text === ',') {
                $tokens[$call_like->get_end_token_pos() - $iteration]->text = '';
            }
            break;
        }
    }
    private function wrap_binary_op_with_brackets(Node $node): void
    {
        if ($this->expr_analyzer->is_expr_with_expr_property_wrappable($node)) {
            $node->expr->set_attribute(Attribute_Key::ORIGINAL_NODE, null);
        }
        if (!$node instanceof Binary_Op) {
            return;
        }
        if ($node->get_attribute(Attribute_Key::ORIGINAL_NODE) instanceof Node) {
            return;
        }
        if ($node->left instanceof Assign && $this->orig_tokens instanceof Token_Stream && !$this->orig_tokens->have_parens($node->left->get_start_token_pos(), $node->left->get_end_token_pos())) {
            $node->left->set_attribute(Attribute_Key::ORIGINAL_NODE, null);
        }
        if ($node->left instanceof Binary_Op && $node->left->get_attribute(Attribute_Key::ORIGINAL_NODE) instanceof Node) {
            $node->left->set_attribute(Attribute_Key::ORIGINAL_NODE, null);
        }
        if ($node->right instanceof Binary_Op && $node->right->get_attribute(Attribute_Key::ORIGINAL_NODE) instanceof Node) {
            $node->right->set_attribute(Attribute_Key::ORIGINAL_NODE, null);
        }
    }
    /**
     * ensure left side is assign and right side is just created
     *
     * @see https://github.com/rectorphp/rector-src/pull/6668
     * @see https://github.com/rectorphp/rector/issues/8980
     * @see https://github.com/rectorphp/rector-src/pull/6653
     */
    private function wrap_assign(Node $left_node, Node $right_node): void
    {
        if ($left_node instanceof Assign && $left_node->get_start_token_pos() > 0 && $right_node->get_start_token_pos() < 0) {
            $left_node->set_attribute(Attribute_Key::WRAPPED_IN_PARENTHESES, \true);
        }
    }
    private function clean_start_indentation_on_heredoc_now_doc(string $content): string
    {
        $lines = New_Line_Splitter::split($content);
        $trimmed_lines = array_map(\Closure::from_callable('ltrim'), $lines);
        return implode("\n", $trimmed_lines);
    }
    private function resolve_indent_spaces(bool $only_level = \false): string
    {
        $indent_size = Simple_Parameter_Provider::provide_int_parameter(Option::INDENT_SIZE);
        if ($only_level) {
            return str_repeat($this->get_indent_character(), $this->indent_level);
        }
        return str_repeat($this->get_indent_character(), $this->indent_level) . str_repeat($this->get_indent_character(), $indent_size);
    }
    /**
     * Must be a method to be able to react to changed parameter in tests
     */
    private function get_indent_character(): string
    {
        return Simple_Parameter_Provider::provide_string_parameter(Option::INDENT_CHAR, ' ');
    }
    /**
     * @param Node[] $stmts
     * @return Node[]|mixed[]
     */
    private function unwrap_file_node(array $stmts): array
    {
        if (count($stmts) === 1 && $stmts[0] instanceof File_Node) {
            return array_values($stmts[0]->stmts);
        }
        return $stmts;
    }
    /**
     * @param Node[] $nodes
     */
    private function contains_nop(array $nodes): bool
    {
        $has_nop = \false;
        foreach ($nodes as $node) {
            // early false when visited Node is InlineHTML
            if ($node instanceof Inline_Html) {
                return \false;
            }
            // use flag to avoid next is InlineHTML that returns early
            if ($node instanceof Nop) {
                $has_nop = \true;
            }
        }
        return $has_nop;
    }
}