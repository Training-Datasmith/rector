<?php

declare (strict_types=1);
namespace Rector\Contract\Php_Parser;

use Php_Parser\Node_Visitor;
/**
 * This interface can be used to tag load node visitor, that will run on parsing files to nodes.
 * It can be used e.g. to decorate nodes with extra attributes,
 * that can be used later in rules or services.
 */
interface Decorating_Node_Visitor_Interface extends Node_Visitor
{
}