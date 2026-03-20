<?php

declare (strict_types=1);
namespace Rector\Dependency_Injection\Php_Stan;

use Php_Stan\Dependency_Injection\Memoizing_Container;
use Php_Stan\Dependency_Injection\Nette\Nette_Container;
use Php_Stan\Parser\Anonymous_Class_Visitor;
use Php_Stan\Parser\Array_Map_Arg_Visitor;
use Php_Stan\Parser\Rich_Parser;
use Rector\Util\Reflection\Privates_Accessor;
/**
 * Helper service to modify PHPStan container
 * To avoid issues caused by node replacement, like @see https://github.com/rectorphp/rector/issues/9492
 */
final class Php_Stan_Container_Memento
{
    public static function remove_rich_visitors(Rich_Parser $rich_parser): void
    {
        // the only way now seems to access container early and remove unwanted services
        // here https://github.com/phpstan/phpstan-src/blob/522421b007cbfc674bebb93e823c774167ac78cd/src/Parser/RichParser.php#L90-L92
        $privates_accessor = new Privates_Accessor();
        /** @var MemoizingContainer $container */
        $container = $privates_accessor->get_private_property($rich_parser, 'container');
        /** @var NetteContainer $originalContainer */
        $original_container = $privates_accessor->get_private_property($container, 'originalContainer');
        /** @var NetteContainer $originalContainer */
        $deeper_container = $privates_accessor->get_private_property($original_container, 'container');
        // get tags property
        $tags = $privates_accessor->get_private_property($deeper_container, 'tags');
        // keep visitors that are useful
        // remove all the rest, https://github.com/phpstan/phpstan-src/tree/1d86de8bb9371534983a8dbcd879e057d2ff028f/src/Parser
        $node_visitors_to_keep = [$container->find_service_names_by_type(Anonymous_Class_Visitor::class)[0] => \true, $container->find_service_names_by_type(Array_Map_Arg_Visitor::class)[0] => \true];
        $tags[Rich_Parser::VISITOR_SERVICE_TAG] = $node_visitors_to_keep;
        $privates_accessor->set_private_property($deeper_container, 'tags', $tags);
    }
}