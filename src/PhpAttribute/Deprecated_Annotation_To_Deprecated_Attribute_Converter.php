<?php

declare (strict_types=1);
namespace Rector\Php_Attribute;

use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Attribute_Group;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Deprecated_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Generic_Tag_Value_Node;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
use Rector\Better_Php_Doc_Parser\Php_Doc_Manipulator\Php_Doc_Tag_Remover;
use Rector\Comments\Node_Doc_Block\Doc_Block_Updater;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Attribute\Node_Factory\Php_Attribute_Group_Factory;
use Rector_Prefix202603\Nette\Utils\Strings;
final class Deprecated_Annotation_To_Deprecated_Attribute_Converter
{
    /**
     * @readonly
     */
    private Php_Doc_Tag_Remover $php_doc_tag_remover;
    /**
     * @readonly
     */
    private Php_Attribute_Group_Factory $php_attribute_group_factory;
    /**
     * @readonly
     */
    private Doc_Block_Updater $doc_block_updater;
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    /**
     * @see https://regex101.com/r/qNytVk/1
     * @var string
     */
    private const VERSION_MATCH_REGEX = '/^(?:(\d+\.\d+\.\d+)\s+)?(.*)$/';
    /**
     * @see https://regex101.com/r/SVDPOB/1
     * @var string
     */
    private const START_STAR_SPACED_REGEX = '#^ *\*#ms';
    public function __construct(Php_Doc_Tag_Remover $php_doc_tag_remover, Php_Attribute_Group_Factory $php_attribute_group_factory, Doc_Block_Updater $doc_block_updater, Php_Doc_Info_Factory $php_doc_info_factory)
    {
        $this->php_doc_tag_remover = $php_doc_tag_remover;
        $this->php_attribute_group_factory = $php_attribute_group_factory;
        $this->doc_block_updater = $doc_block_updater;
        $this->php_doc_info_factory = $php_doc_info_factory;
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Stmt\Trait_ $node
     */
    public function convert($node): ?Node
    {
        $has_changed = \false;
        $php_doc_info = $this->php_doc_info_factory->create_from_node($node);
        if ($php_doc_info instanceof Php_Doc_Info) {
            $deprecated_attribute_group = $this->handle_deprecated($php_doc_info);
            if ($deprecated_attribute_group instanceof Attribute_Group) {
                $this->doc_block_updater->update_refactored_node_with_php_doc_info($node);
                $node->attr_groups = array_merge($node->attr_groups, [$deprecated_attribute_group]);
                $this->remove_deprecated_annotations($php_doc_info);
                $has_changed = \true;
            }
        }
        return $has_changed ? $node : null;
    }
    private function handle_deprecated(Php_Doc_Info $php_doc_info): ?Attribute_Group
    {
        $attribute_group = null;
        $desired_tag_value_nodes = $php_doc_info->get_tags_by_name('deprecated');
        foreach ($desired_tag_value_nodes as $desired_tag_value_node) {
            if (!$desired_tag_value_node->value instanceof Deprecated_Tag_Value_Node) {
                continue;
            }
            $attribute_group = $this->create_attribute_group($desired_tag_value_node->value->description);
            $this->php_doc_tag_remover->remove_tag_value_from_node($php_doc_info, $desired_tag_value_node);
            break;
        }
        return $attribute_group;
    }
    private function create_attribute_group(string $annotation_value): Attribute_Group
    {
        $matches = Strings::match($annotation_value, self::VERSION_MATCH_REGEX);
        if ($matches === null) {
            $annotation_value = Strings::replace($annotation_value, self::START_STAR_SPACED_REGEX, '');
            return new Attribute_Group([new Attribute(new Fully_Qualified('Deprecated'), [new Arg(new String_($annotation_value, [Attribute_Key::KIND => String_::KIND_NOWDOC, Attribute_Key::DOC_LABEL => 'TXT']), \false, \false, [], new Identifier('message'))])]);
        }
        $since = $matches[1] ?? null;
        $message = $matches[2] ?? null;
        return $this->php_attribute_group_factory->create_from_class_with_items('Deprecated', array_filter(['message' => $message, 'since' => $since]));
    }
    private function remove_deprecated_annotations(Php_Doc_Info $php_doc_info): bool
    {
        $has_changed = \false;
        $desired_tag_value_nodes = $php_doc_info->get_tags_by_name('deprecated');
        foreach ($desired_tag_value_nodes as $desired_tag_value_node) {
            if (!$desired_tag_value_node->value instanceof Generic_Tag_Value_Node) {
                continue;
            }
            $this->php_doc_tag_remover->remove_tag_value_from_node($php_doc_info, $desired_tag_value_node);
            $has_changed = \true;
        }
        return $has_changed;
    }
}