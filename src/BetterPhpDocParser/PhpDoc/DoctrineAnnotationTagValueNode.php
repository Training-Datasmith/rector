<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc;

use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation\Abstract_Values_Aware_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
final class Doctrine_Annotation_Tag_Value_Node extends Abstract_Values_Aware_Node
{
    public Identifier_Type_Node $identifier_type_node;
    /**
     * @param ArrayItemNode[] $values
     */
    public function __construct(Identifier_Type_Node $identifier_type_node, ?string $original_content = null, array $values = [], ?string $silent_key = null)
    {
        $this->identifier_type_node = $identifier_type_node;
        $this->has_changed = \true;
        parent::__construct($values, $original_content, $silent_key);
    }
    public function __toString(): string
    {
        if (!$this->has_changed) {
            if ($this->original_content === null) {
                return '';
            }
            return $this->original_content;
        }
        if ($this->values === []) {
            if ($this->original_content === '()') {
                // empty brackets
                return $this->original_content;
            }
            return '';
        }
        $item_contents = $this->print_values_content($this->values);
        return \sprintf('(%s)', $item_contents);
    }
    public function has_class_name(string $class_name): bool
    {
        $annotation_name = trim($this->identifier_type_node->name, '@');
        if ($annotation_name === $class_name) {
            return \true;
        }
        // the name is not fully qualified in the original name, look for resolved class attribute
        $resolved_class = $this->identifier_type_node->get_attribute(Php_Doc_Attribute_Key::RESOLVED_CLASS);
        return $resolved_class === $class_name;
    }
}