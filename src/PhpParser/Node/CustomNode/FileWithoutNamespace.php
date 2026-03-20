<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node\Custom_Node;

use Override;
use Rector\Contract\Php_Parser\Node\Stmts_Aware_Interface;
use Rector\Php_Parser\Node\File_Node;
/**
 * @deprecated Use @see \Rector\PhpParser\Node\FileNode instead
 * @api
 *
 * Inspired by https://github.com/phpstan/phpstan-src/commit/ed81c3ad0b9877e6122c79b4afda9d10f3994092
 */
final class File_Without_Namespace extends File_Node implements Stmts_Aware_Interface
{
    #[Override]
    public function get_type(): string
    {
        return 'FileWithoutNamespace';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function get_sub_node_names(): array
    {
        return ['stmts'];
    }
}