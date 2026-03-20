<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Attributes;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
final class Attribute_Mirrorer
{
    /**
     * @var string[]
     */
    private const ATTRIBUTES_TO_MIRROR = [Php_Doc_Attribute_Key::PARENT, Php_Doc_Attribute_Key::START_AND_END, Php_Doc_Attribute_Key::ORIG_NODE];
    public function mirror(Node $old_node, Node $new_node): void
    {
        foreach (self::ATTRIBUTES_TO_MIRROR as $attribute_to_mirror) {
            if (!$old_node->has_attribute($attribute_to_mirror)) {
                continue;
            }
            $attribute_value = $old_node->get_attribute($attribute_to_mirror);
            $new_node->set_attribute($attribute_to_mirror, $attribute_value);
        }
    }
}