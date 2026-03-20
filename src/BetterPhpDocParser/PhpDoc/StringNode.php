<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc;

use Php_Parser\Node\Scalar\String_;
use Php_Stan\Php_Doc_Parser\Ast\Node_Attributes;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Value_Node;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class String_Node implements Php_Doc_Tag_Value_Node
{
    use Node_Attributes;
    public string $value;
    public function __construct(string $value)
    {
        $this->value = $value;
        $this->value = str_replace('""', '"', $this->value);
        if (strpos($this->value, "'") !== \false && strpos($this->value, "\n") === \false) {
            $kind = String_::KIND_DOUBLE_QUOTED;
        } else {
            $kind = String_::KIND_SINGLE_QUOTED;
        }
        $this->set_attribute(Attribute_Key::KIND, $kind);
    }
    public function __toString(): string
    {
        return '"' . str_replace('"', '""', $this->value) . '"';
    }
}