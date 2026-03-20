<?php

declare (strict_types=1);
namespace Rector\Php_Attribute;

use Php_Parser\Node\Identifier;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php80\Contract\Value_Object\Annotation_To_Attribute_Interface;
use Rector\Php_Attribute\Value_Object\Use_Alias_Metadata;
/**
 * @see \Rector\Tests\PhpAttribute\UseAliasNameMatcherTest
 */
final class Use_Alias_Name_Matcher
{
    /**
     * @param Use_[] $uses
     */
    public function match(array $uses, string $short_annotation_name, Annotation_To_Attribute_Interface $annotation_to_attribute): ?Use_Alias_Metadata
    {
        $short_annotation_name = trim($short_annotation_name, '@');
        foreach ($uses as $use) {
            foreach ($use->uses as $use_use) {
                // we need to use original use statement
                $original_use_use_node = $use_use->get_attribute(Attribute_Key::ORIGINAL_NODE);
                if (!$original_use_use_node instanceof Use_Item) {
                    continue;
                }
                if (!$original_use_use_node->alias instanceof Identifier) {
                    continue;
                }
                $alias = $original_use_use_node->alias->to_string();
                if (strncmp($short_annotation_name, $alias, strlen($alias)) !== 0) {
                    continue;
                }
                $fully_qualified_annotation_name = $original_use_use_node->name->to_string() . ltrim($short_annotation_name, $alias);
                if ($fully_qualified_annotation_name !== $annotation_to_attribute->get_tag()) {
                    continue;
                }
                $annotation_parts = explode('\\', $fully_qualified_annotation_name);
                $attribute_parts = explode('\\', $annotation_to_attribute->get_attribute_class());
                // requirement for matching single part rename
                if (count($annotation_parts) !== count($attribute_parts)) {
                    continue;
                }
                // now we are matching correct contact and old and new have the same number of parts
                $use_import_part_count = substr_count($original_use_use_node->name->to_string(), '\\') + 1;
                $new_attribute_import_part = array_slice($attribute_parts, 0, $use_import_part_count);
                $new_attribute_import = implode('\\', $new_attribute_import_part);
                $short_name_part_count = count($attribute_parts) - $use_import_part_count;
                // +1, to remove the alias part
                $attribute_parts = array_slice($attribute_parts, -$short_name_part_count);
                $short_attribute_name = $alias . '\\' . implode('\\', $attribute_parts);
                return new Use_Alias_Metadata($short_attribute_name, $new_attribute_import, $use_use);
            }
        }
        return null;
    }
}