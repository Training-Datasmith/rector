<?php

declare (strict_types=1);
namespace Rector\Contract\Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node\Stmt;
/**
 * @api
 * @deprecated This interface is deprecated since Rector 2.2.9 as changing node classes. Use @see NodeGroup::STMTS_AWARE instead.
 *
 * @property Stmt[]|null $stmts
 */
interface Stmts_Aware_Interface extends Node
{
}