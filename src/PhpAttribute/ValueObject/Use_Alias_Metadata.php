<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Value_Object;

use Php_Parser\Node\Use_Item;
final class Use_Alias_Metadata
{
    /**
     * @readonly
     */
    private string $short_attribute_name;
    /**
     * @readonly
     */
    private string $use_import_name;
    /**
     * @readonly
     */
    private Use_Item $use_item;
    public function __construct(string $short_attribute_name, string $use_import_name, Use_Item $use_item)
    {
        $this->short_attribute_name = $short_attribute_name;
        $this->use_import_name = $use_import_name;
        $this->use_item = $use_item;
    }
    public function get_short_attribute_name(): string
    {
        return $this->short_attribute_name;
    }
    public function get_use_import_name(): string
    {
        return $this->use_import_name;
    }
    public function get_use_use(): Use_Item
    {
        return $this->use_item;
    }
}