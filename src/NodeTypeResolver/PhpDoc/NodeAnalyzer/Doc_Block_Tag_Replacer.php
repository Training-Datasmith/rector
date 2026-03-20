<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Doc\Node_Analyzer;

use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Generic_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Node;
use Rector\Better_Php_Doc_Parser\Annotation\Annotation_Naming;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
final class Doc_Block_Tag_Replacer
{
    /**
     * @readonly
     */
    private Annotation_Naming $annotation_naming;
    public function __construct(Annotation_Naming $annotation_naming)
    {
        $this->annotation_naming = $annotation_naming;
    }
    public function replace_tag_by_another(Php_Doc_Info $php_doc_info, string $old_tag, string $new_tag): bool
    {
        $has_changed = \false;
        $old_tag = $this->annotation_naming->normalize_name($old_tag);
        $new_tag = $this->annotation_naming->normalize_name($new_tag);
        $php_doc_node = $php_doc_info->get_php_doc_node();
        foreach ($php_doc_node->children as $key => $php_doc_child_node) {
            if (!$php_doc_child_node instanceof Php_Doc_Tag_Node) {
                continue;
            }
            if ($php_doc_child_node->name !== $old_tag) {
                continue;
            }
            unset($php_doc_node->children[$key]);
            $php_doc_node->children[] = new Php_Doc_Tag_Node($new_tag, new Generic_Tag_Value_Node(''));
            $has_changed = \true;
        }
        return $has_changed;
    }
}