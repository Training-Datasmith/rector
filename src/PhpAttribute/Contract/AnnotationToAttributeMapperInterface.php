<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Contract;

use Php_Parser\Node;
/**
 * @template T as mixed
 */
interface Annotation_To_Attribute_Mapper_Interface
{
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool;
    /**
     * @param T $value
     */
    public function map($value): Node;
}