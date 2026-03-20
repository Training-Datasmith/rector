<?php

declare (strict_types=1);
namespace Rector\Util;

use Php_Parser\Node;
use Rector\Custom_Rules\Simple_Node_Dumper;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class Node_Printer
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @see https://regex101.com/r/Fe8n73/1
     * @var string
     */
    private const CLASS_NAME_REGEX = '#(?<class_name>PhpParser(.*?))\(#ms';
    /**
     * @see https://regex101.com/r/uQFuvL/1
     * @var string
     */
    private const PROPERTY_KEY_REGEX = '#(?<key>[\w\d]+)\:#';
    public function __construct(Symfony_Style $symfony_style)
    {
        $this->symfony_style = $symfony_style;
    }
    /**
     * @param Node|Node[] $nodes
     */
    public function print_nodes($nodes): void
    {
        $dumped_nodes_contents = Simple_Node_Dumper::dump($nodes);
        // colorize
        $color_contents = $this->add_console_colors($dumped_nodes_contents);
        $this->symfony_style->writeln($color_contents);
        $this->symfony_style->new_line();
    }
    private function add_console_colors(string $contents): string
    {
        // decorate class names
        $color_contents = Strings::replace($contents, self::CLASS_NAME_REGEX, static fn(array $match): string => '<fg=green>' . $match['class_name'] . '</>(');
        // decorate keys
        return Strings::replace($color_contents, self::PROPERTY_KEY_REGEX, static fn(array $match): string => '<fg=yellow>' . $match['key'] . '</>:');
    }
}