<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Value_Object\Type;

use Override;
use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
/**
 * @api
 */
final class Aliased_Object_Type extends Object_Type
{
    /**
     * @readonly
     */
    private string $fully_qualified_class;
    public function __construct(string $alias, string $fully_qualified_class)
    {
        $this->fully_qualified_class = $fully_qualified_class;
        parent::__construct($alias);
    }
    public function get_fully_qualified_name(): string
    {
        return $this->fully_qualified_class;
    }
    /**
     * @param Use_::TYPE_* $useType
     */
    public function get_use_node(int $use_type): Use_
    {
        $name = new Name($this->fully_qualified_class);
        $use_item = new Use_Item($name, $this->get_class_name());
        $use = new Use_([$use_item]);
        $use->type = $use_type;
        return $use;
    }
    public function get_short_name(): string
    {
        return $this->get_class_name();
    }
    /**
     * @param $this|\Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType $comparedObjectType
     */
    public function are_short_names_equal($compared_object_type): bool
    {
        return $this->get_short_name() === $compared_object_type->get_short_name();
    }
    #[Override]
    public function equals(Type $type): bool
    {
        $class_name = Class_Name_From_Object_Type_Resolver::resolve($type);
        // compare with FQN classes
        if ($class_name !== null) {
            if ($type instanceof self && $this->fully_qualified_class === $type->get_fully_qualified_name()) {
                return \true;
            }
            if ($this->fully_qualified_class === $class_name) {
                return \true;
            }
        }
        return parent::equals($type);
    }
}