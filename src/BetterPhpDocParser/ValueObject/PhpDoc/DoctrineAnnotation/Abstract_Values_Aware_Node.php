<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation;

use Php_Stan\Php_Doc_Parser\Ast\Node_Attributes;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc\String_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
abstract class Abstract_Values_Aware_Node implements Php_Doc_Tag_Value_Node
{
    use Node_Attributes;
    /**
     * @var ArrayItemNode[]
     */
    public array $values = [];
    protected ?string $original_content = null;
    protected ?string $silent_key = null;
    protected bool $has_changed = \false;
    /**
     * @param ArrayItemNode[] $values Must be public so node traverser can go through them
     */
    public function __construct(array $values = [], ?string $original_content = null, ?string $silent_key = null)
    {
        $this->values = $values;
        $this->original_content = $original_content;
        $this->silent_key = $silent_key;
    }
    /**
     * @api
     */
    public function remove_value(string $desired_key): void
    {
        foreach ($this->values as $key => $value) {
            if (!$this->is_value_key_equals($value, $desired_key)) {
                continue;
            }
            unset($this->values[$key]);
            // invoke reprint
            $this->set_attribute(Php_Doc_Attribute_Key::ORIG_NODE, null);
        }
    }
    /**
     * @return ArrayItemNode[]
     */
    public function get_values(): array
    {
        return $this->values;
    }
    /**
     * @return ArrayItemNode[]
     */
    public function get_values_with_silent_key(): array
    {
        if ($this->silent_key === null) {
            return $this->values;
        }
        // to keep original values untouched, unless not changed
        $silent_key_aware_values = $this->values;
        foreach ($silent_key_aware_values as $silent_key_aware_value) {
            if ($silent_key_aware_value->key === null) {
                $silent_key_aware_value->key = $this->silent_key;
                break;
            }
        }
        return $silent_key_aware_values;
    }
    public function get_value(string $desired_key): ?Array_Item_Node
    {
        foreach ($this->values as $value) {
            if ($this->is_value_key_equals($value, $desired_key)) {
                return $value;
            }
        }
        return null;
    }
    public function get_silent_value(): ?Array_Item_Node
    {
        foreach ($this->values as $value) {
            if ($value->key === null) {
                return $value;
            }
        }
        return null;
    }
    public function mark_as_changed(): void
    {
        $this->has_changed = \true;
    }
    public function get_original_content(): ?string
    {
        return $this->original_content;
    }
    /**
     * @param mixed[] $values
     */
    protected function print_values_content(array $values): string
    {
        $item_contents = '';
        $last_item_key = array_key_last($values);
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $item_contents .= $this->stringify_value($value);
            } else {
                $item_contents .= $key . '=' . $this->stringify_value($value);
            }
            if ($last_item_key !== $key) {
                $item_contents .= ', ';
            }
        }
        return $item_contents;
    }
    private function is_value_key_equals(Array_Item_Node $array_item_node, string $desired_key): bool
    {
        if ($array_item_node->key instanceof String_Node) {
            return $array_item_node->key->value === $desired_key;
        }
        return $array_item_node->key === $desired_key;
    }
    /**
     * @param mixed $value
     */
    private function stringify_value($value): string
    {
        // @todo resolve original casing
        if ($value === \false) {
            return 'false';
        }
        if ($value === \true) {
            return 'true';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            return $this->print_values_content($value);
        }
        return (string) $value;
    }
}