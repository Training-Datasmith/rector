<?php

declare (strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDoc;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;

final class ArrayItemNode implements PhpDocTagValueNode
{
    use NodeAttributes;
    /**
     * @var mixed
     */
    public $value;
    /**
     * @var mixed
     */
    public $key;
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function __construct($value, $key = null)
    {
        $this->value = $value;
        $this->key = $key;
    }
    public function __toString(): string
    {
        $value = '';
        if ($this->key !== null && !is_int($this->key)) {
            $value .= $this->key . '=';
        }
        if (is_array($this->value)) {
            foreach ($this->value as $singleValue) {
                $value .= $singleValue;
            }
        } elseif ($this->value instanceof \Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode) {
            $value .= '@' . ltrim((string) $this->value->identifierTypeNode, '@') . $this->value;
        } else {
            $value .= $this->value;
        }
        return $value;
    }
}
