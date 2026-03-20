<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Manipulator;

use Php_Parser\Node\Function_Like;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Fetch_Node;
use Php_Stan\Php_Doc_Parser\Ast\Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Param_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Return_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Var_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Array_Shape_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Const_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Generic_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Never_Type;
use Php_Stan\Type\Type;
use Rector\Better_Php_Doc_Parser\Guard\New_Php_Doc_From_Php_Stan_Type_Guard;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Intersection_Type_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Union_Type_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Spacing_Aware_Array_Type_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Spacing_Aware_Callable_Type_Node;
use Rector\Comments\Node_Doc_Block\Doc_Block_Updater;
use Rector\Node_Type_Resolver\Type_Comparator\Type_Comparator;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
use Rector\Type_Declaration\Php_Doc_Parser\Param_Php_Doc_Node_Factory;
final class Php_Doc_Type_Changer
{
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private Type_Comparator $type_comparator;
    /**
     * @readonly
     */
    private Param_Php_Doc_Node_Factory $param_php_doc_node_factory;
    /**
     * @readonly
     */
    private New_Php_Doc_From_Php_Stan_Type_Guard $new_php_doc_from_php_stan_type_guard;
    /**
     * @readonly
     */
    private Doc_Block_Updater $doc_block_updater;
    /**
     * @var array<class-string<Node>>
     */
    private const ALLOWED_TYPES = [Generic_Type_Node::class, Spacing_Aware_Array_Type_Node::class, Spacing_Aware_Callable_Type_Node::class, Array_Shape_Node::class];
    /**
     * @var string[]
     */
    private const ALLOWED_IDENTIFIER_TYPENODE_TYPES = ['class-string'];
    public function __construct(Static_Type_Mapper $static_type_mapper, Type_Comparator $type_comparator, Param_Php_Doc_Node_Factory $param_php_doc_node_factory, New_Php_Doc_From_Php_Stan_Type_Guard $new_php_doc_from_php_stan_type_guard, Doc_Block_Updater $doc_block_updater)
    {
        $this->static_type_mapper = $static_type_mapper;
        $this->type_comparator = $type_comparator;
        $this->param_php_doc_node_factory = $param_php_doc_node_factory;
        $this->new_php_doc_from_php_stan_type_guard = $new_php_doc_from_php_stan_type_guard;
        $this->doc_block_updater = $doc_block_updater;
    }
    public function change_var_type(Stmt $stmt, Php_Doc_Info $php_doc_info, Type $new_type): bool
    {
        // better skip, could crash hard
        if ($php_doc_info->has_invalid_tag('@var')) {
            return \false;
        }
        // make sure the tags are not identical, e.g imported class vs FQN class
        if ($this->type_comparator->are_types_equal($php_doc_info->get_var_type(), $new_type)) {
            return \false;
        }
        // prevent existing type override by mixed
        if (!$php_doc_info->get_var_type() instanceof Mixed_Type && $new_type instanceof Constant_Array_Type && $new_type->get_iterable_value_type() instanceof Never_Type) {
            return \false;
        }
        if (!$this->new_php_doc_from_php_stan_type_guard->is_legal($new_type)) {
            return \false;
        }
        // override existing type
        $new_php_stan_php_doc_type_node = $this->static_type_mapper->map_php_stan_type_to_php_stan_php_doc_type_node($new_type);
        $current_var_tag_value_node = $php_doc_info->get_var_tag_value_node();
        if ($current_var_tag_value_node instanceof Var_Tag_Value_Node) {
            // only change type
            $current_var_tag_value_node->type = $new_php_stan_php_doc_type_node;
        } else {
            // add completely new one
            $var_tag_value_node = new Var_Tag_Value_Node($new_php_stan_php_doc_type_node, '', '');
            $php_doc_info->add_tag_value_node($var_tag_value_node);
        }
        $this->doc_block_updater->update_refactored_node_with_php_doc_info($stmt);
        return \true;
    }
    public function change_return_type_node(Function_Like $function_like, Php_Doc_Info $php_doc_info, Type_Node $new_type_node): void
    {
        $existing_return_tag_value_node = $php_doc_info->get_return_tag_value();
        if ($existing_return_tag_value_node instanceof Return_Tag_Value_Node) {
            // enforce reprint of copied type node
            $new_type_node->set_attribute('orig_node', null);
            $existing_return_tag_value_node->type = $new_type_node;
        } else {
            $return_tag_value_node = new Return_Tag_Value_Node($new_type_node, '');
            $php_doc_info->add_tag_value_node($return_tag_value_node);
        }
        $this->doc_block_updater->update_refactored_node_with_php_doc_info($function_like);
    }
    public function change_param_type_node(Function_Like $function_like, Php_Doc_Info $php_doc_info, Param $param, string $param_name, Type_Node $new_type_node): void
    {
        $existing_param_tag_value_node = $php_doc_info->get_param_tag_value_by_name($param_name);
        if ($existing_param_tag_value_node instanceof Param_Tag_Value_Node) {
            $existing_param_tag_value_node->type = $new_type_node;
        } else {
            $param_tag_value_node = $this->param_php_doc_node_factory->create($new_type_node, $param);
            $php_doc_info->add_tag_value_node($param_tag_value_node);
        }
        $this->doc_block_updater->update_refactored_node_with_php_doc_info($function_like);
    }
    public function change_return_type(Function_Like $function_like, Php_Doc_Info $php_doc_info, Type $new_type): bool
    {
        // better not touch this, can crash
        if ($php_doc_info->has_invalid_tag('@return')) {
            return \false;
        }
        // make sure the tags are not identical, e.g imported class vs FQN class
        if ($this->type_comparator->are_types_equal($php_doc_info->get_return_type(), $new_type)) {
            return \false;
        }
        if (!$this->new_php_doc_from_php_stan_type_guard->is_legal($new_type)) {
            return \false;
        }
        // override existing type
        $new_php_stan_php_doc_type_node = $this->static_type_mapper->map_php_stan_type_to_php_stan_php_doc_type_node($new_type);
        $current_return_tag_value_node = $php_doc_info->get_return_tag_value();
        if ($current_return_tag_value_node instanceof Return_Tag_Value_Node) {
            // only change type
            $current_return_tag_value_node->type = $new_php_stan_php_doc_type_node;
        } else {
            // add completely new one
            $return_tag_value_node = new Return_Tag_Value_Node($new_php_stan_php_doc_type_node, '');
            $php_doc_info->add_tag_value_node($return_tag_value_node);
        }
        $this->doc_block_updater->update_refactored_node_with_php_doc_info($function_like);
        return \true;
    }
    public function change_param_type(Function_Like $function_like, Php_Doc_Info $php_doc_info, Type $new_type, Param $param, string $param_name): bool
    {
        // better skip, could crash hard
        if ($php_doc_info->has_invalid_tag('@param')) {
            return \false;
        }
        if (!$this->new_php_doc_from_php_stan_type_guard->is_legal($new_type)) {
            return \false;
        }
        $php_doc_type_node = $this->static_type_mapper->map_php_stan_type_to_php_stan_php_doc_type_node($new_type);
        $param_tag_value_node = $php_doc_info->get_param_tag_value_by_name($param_name);
        // override existing type
        if ($param_tag_value_node instanceof Param_Tag_Value_Node) {
            // already set
            $current_type = $this->static_type_mapper->map_php_stan_php_doc_type_node_to_php_stan_type($param_tag_value_node->type, $param);
            // avoid overriding better type
            if ($this->type_comparator->is_subtype($current_type, $new_type)) {
                return \false;
            }
            if ($this->type_comparator->are_types_equal($current_type, $new_type)) {
                return \false;
            }
            $param_tag_value_node->type = $php_doc_type_node;
        } else {
            $param_tag_value_node = $this->param_php_doc_node_factory->create($php_doc_type_node, $param);
            $php_doc_info->add_tag_value_node($param_tag_value_node);
        }
        $this->doc_block_updater->update_refactored_node_with_php_doc_info($function_like);
        return \true;
    }
    public function is_allowed(Type_Node $type_node): bool
    {
        if ($type_node instanceof Brackets_Aware_Union_Type_Node || $type_node instanceof Brackets_Aware_Intersection_Type_Node) {
            foreach ($type_node->types as $type) {
                if ($this->is_allowed($type)) {
                    return \true;
                }
            }
        }
        if ($type_node instanceof Const_Type_Node && $type_node->const_expr instanceof Const_Fetch_Node) {
            return \true;
        }
        if (in_array(get_class($type_node), self::ALLOWED_TYPES, \true)) {
            return \true;
        }
        if (!$type_node instanceof Identifier_Type_Node) {
            return \false;
        }
        return in_array((string) $type_node, self::ALLOWED_IDENTIFIER_TYPENODE_TYPES, \true);
    }
    /**
     * @api downgrade
     */
    public function change_var_type_node(Stmt $stmt, Php_Doc_Info $php_doc_info, Type_Node $type_node): void
    {
        $existing_var_tag_value_node = $php_doc_info->get_var_tag_value_node();
        if ($existing_var_tag_value_node instanceof Var_Tag_Value_Node) {
            $existing_var_tag_value_node->type = $type_node;
        } else {
            // add completely new one
            $var_tag_value_node = new Var_Tag_Value_Node($type_node, '', '');
            $php_doc_info->add_tag_value_node($var_tag_value_node);
        }
        $this->doc_block_updater->update_refactored_node_with_php_doc_info($stmt);
    }
}