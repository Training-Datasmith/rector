<?php

declare (strict_types=1);
namespace Rector_Prefix202603;

use Php_Parser\Node;
use Php_Parser\Pretty_Printer\Standard;
use Rector\Console\Style\Symfony_Style_Factory;
use Rector\Php_Parser\Node\File_Node;
use Rector\Util\Node_Printer;
use Rector_Prefix202603\Illuminate\Container\Container;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
if (!\function_exists('print_node')) {
    /**
     * @param Node|Node[] $node
     */
    function print_node($node): void
    {
        $standard = new Standard();
        $nodes = \is_array($node) ? $node : [$node];
        if ($nodes[0] instanceof File_Node) {
            $nodes = $nodes[0]->stmts;
        }
        foreach ($nodes as $node) {
            $printed_content = $standard->pretty_print([$node]);
            \var_dump($printed_content);
        }
    }
}
if (!\function_exists('dump_node')) {
    /**
     * @param Node|Node[] $node
     */
    function dump_node($node): void
    {
        $rector_style = Container::get_instance()->make(Symfony_Style_Factory::class)->create();
        // we turn up the verbosity so it's visible in tests overriding the
        // default which is to be quite during tests
        $rector_style->set_verbosity(Output_Interface::VERBOSITY_VERBOSE);
        $rector_style->new_line();
        $node_printer = new Node_Printer($rector_style);
        $node_printer->print_nodes($node);
    }
}