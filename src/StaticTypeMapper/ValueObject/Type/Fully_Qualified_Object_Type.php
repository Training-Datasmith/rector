<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Value_Object\Type;

use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
use Php_Stan\Type\Object_Type;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @api
 */
final class Fully_Qualified_Object_Type extends Object_Type
{
    public function get_short_name_type(): \Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type
    {
        return new \Rector\Static_Type_Mapper\Value_Object\Type\Shortened_Object_Type($this->get_short_name(), $this->get_class_name());
    }
    /**
     * @param \Rector\StaticTypeMapper\ValueObject\Type\AliasedObjectType|$this $comparedObjectType
     */
    public function are_short_names_equal($compared_object_type): bool
    {
        return $this->get_short_name() === $compared_object_type->get_short_name();
    }
    public function get_short_name(): string
    {
        $class_name = $this->get_class_name();
        if (strpos($class_name, '\\') === \false) {
            return $class_name;
        }
        return (string) Strings::after($class_name, '\\', -1);
    }
    public function get_short_name_node(): Name
    {
        $name = new Name($this->get_short_name());
        // keep original to avoid loss on while importing
        $name->set_attribute(Attribute_Key::NAMESPACED_NAME, $this->get_class_name());
        return $name;
    }
    /**
     * @param Use_::TYPE_* $useType
     */
    public function get_use_node(int $use_type): Use_
    {
        $name = new Name($this->get_class_name());
        $use_item = new Use_Item($name);
        $use = new Use_([$use_item]);
        $use->type = $use_type;
        return $use;
    }
    public function get_short_name_lowered(): string
    {
        return strtolower($this->get_short_name());
    }
}