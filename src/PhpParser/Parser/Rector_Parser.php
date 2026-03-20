<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Parser;

use Php_Parser\Node\Stmt;
use Php_Parser\Parser_Factory;
use Php_Parser\Php_Version;
use Php_Stan\Parser\Parser;
use Php_Stan\Parser\Rich_Parser;
use Rector\Dependency_Injection\Php_Stan\Php_Stan_Container_Memento;
use Rector\Php_Parser\Value_Object\Stmts_And_Tokens;
use Rector\Util\Reflection\Privates_Accessor;
final class Rector_Parser
{
    /**
     * @var RichParser
     * @readonly
     */
    private Parser $parser;
    /**
     * @readonly
     */
    private Privates_Accessor $privates_accessor;
    /**
     * @param RichParser $parser
     */
    public function __construct(Parser $parser, Privates_Accessor $privates_accessor)
    {
        $this->parser = $parser;
        $this->privates_accessor = $privates_accessor;
        Php_Stan_Container_Memento::remove_rich_visitors($parser);
    }
    /**
     * @api used by rector-symfony
     *
     * @return Stmt[]
     */
    public function parse_file(string $file_path): array
    {
        return $this->parser->parse_file($file_path);
    }
    /**
     * @return Stmt[]
     */
    public function parse_string(string $file_content): array
    {
        return $this->parser->parse_string($file_content);
    }
    public function parse_file_content_to_stmts_and_tokens(string $file_content, bool $for_newest_supported_version = \true): Stmts_And_Tokens
    {
        if (!$for_newest_supported_version) {
            // don't directly change PHPStan Parser service
            // to avoid reuse on next file
            $phpstan_parser = clone $this->parser;
            $parser_factory = new Parser_Factory();
            $parser = $parser_factory->create_for_version(Php_Version::from_string('7.0'));
            $this->privates_accessor->set_private_property($phpstan_parser, 'parser', $parser);
            return $this->resolve_stmts_and_tokens($phpstan_parser, $file_content);
        }
        return $this->resolve_stmts_and_tokens($this->parser, $file_content);
    }
    private function resolve_stmts_and_tokens(Parser $parser, string $file_content): Stmts_And_Tokens
    {
        $stmts = $parser->parse_string($file_content);
        $inner_parser = $this->privates_accessor->get_private_property($parser, 'parser');
        $tokens = $inner_parser->get_tokens();
        return new Stmts_And_Tokens($stmts, $tokens);
    }
}