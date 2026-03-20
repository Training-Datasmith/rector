<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc;

use Php_Stan\Php_Doc_Parser\Ast\Node_Attributes;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Value_Node;
final class Array_Item_Node implements Php_Doc_Tag_Value_Node
{
    use Node_Attributes;
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
            foreach ($this->value as $single_value) {
                $value .= $single_value;
            }
        } elseif ($this->value instanceof \Rector\Better_Php_Doc_Parser\Php_Doc\Doctrine_Annotation_Tag_Value_Node) {
            $value .= '@' . ltrim((string) $this->value->identifier_type_node, '@') . $this->value;
        } else {
            $value .= $this->value;
        }
        return $value;
    }
}