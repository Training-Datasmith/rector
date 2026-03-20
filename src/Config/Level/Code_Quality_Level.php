<?php

declare (strict_types=1);
namespace Rector\Config\Level;

use Rector\Code_Quality\Rector\Assign\Combined_Assign_Rector;
use Rector\Code_Quality\Rector\Attribute\Sort_Attribute_Named_Args_Rector;
use Rector\Code_Quality\Rector\Boolean_And\Remove_Useless_Is_Object_Check_Rector;
use Rector\Code_Quality\Rector\Boolean_And\Repeated_And_Not_Equal_To_Not_In_Array_Rector;
use Rector\Code_Quality\Rector\Boolean_And\Simplify_Empty_Array_Check_Rector;
use Rector\Code_Quality\Rector\Boolean_Not\Replace_Constant_Boolean_Not_Rector;
use Rector\Code_Quality\Rector\Boolean_Not\Replace_Multiple_Boolean_Not_Rector;
use Rector\Code_Quality\Rector\Boolean_Not\Simplify_De_Morgan_Binary_Rector;
use Rector\Code_Quality\Rector\Boolean_Or\Repeated_Or_Equal_To_In_Array_Rector;
use Rector\Code_Quality\Rector\Catch_\Throw_With_Previous_Exception_Rector;
use Rector\Code_Quality\Rector\Class_\Complete_Dynamic_Properties_Rector;
use Rector\Code_Quality\Rector\Class_\Convert_Static_To_Self_Rector;
use Rector\Code_Quality\Rector\Class_\Inline_Constructor_Default_To_Property_Rector;
use Rector\Code_Quality\Rector\Class_\Remove_Readonly_Property_Visibility_On_Readonly_Class_Rector;
use Rector\Code_Quality\Rector\Class_Const_Fetch\Variable_Const_Fetch_To_Class_Const_Fetch_Rector;
use Rector\Code_Quality\Rector\Class_Method\Explicit_Return_Null_Rector;
use Rector\Code_Quality\Rector\Class_Method\Inline_Array_Return_Assign_Rector;
use Rector\Code_Quality\Rector\Class_Method\Locally_Called_Static_Method_To_Non_Static_Rector;
use Rector\Code_Quality\Rector\Class_Method\Optional_Parameters_After_Required_Rector;
use Rector\Code_Quality\Rector\Concat\Join_String_Concat_Rector;
use Rector\Code_Quality\Rector\Empty_\Simplify_Empty_Check_On_Empty_Array_Rector;
use Rector\Code_Quality\Rector\Equal\Use_Identical_Over_Equal_With_Same_Type_Rector;
use Rector\Code_Quality\Rector\Expression\Inline_If_To_Explicit_If_Rector;
use Rector\Code_Quality\Rector\Expression\Ternary_False_Expression_To_If_Rector;
use Rector\Code_Quality\Rector\For_\For_Repeated_Count_To_Own_Variable_Rector;
use Rector\Code_Quality\Rector\Foreach_\Foreach_Items_Assign_To_Empty_Array_To_Assign_Rector;
use Rector\Code_Quality\Rector\Foreach_\Foreach_To_In_Array_Rector;
use Rector\Code_Quality\Rector\Foreach_\Simplify_Foreach_To_Coalescing_Rector;
use Rector\Code_Quality\Rector\Foreach_\Unused_Foreach_Value_To_Array_Keys_Rector;
use Rector\Code_Quality\Rector\Func_Call\Array_Merge_Of_Non_Arrays_To_Simple_Array_Rector;
use Rector\Code_Quality\Rector\Func_Call\Call_User_Func_With_Arrow_Function_To_Inline_Rector;
use Rector\Code_Quality\Rector\Func_Call\Change_Array_Push_To_Array_Assign_Rector;
use Rector\Code_Quality\Rector\Func_Call\Compact_To_Variables_Rector;
use Rector\Code_Quality\Rector\Func_Call\Inline_Is_A_Instance_Of_Rector;
use Rector\Code_Quality\Rector\Func_Call\Is_A_With_String_With_Third_Argument_Rector;
use Rector\Code_Quality\Rector\Func_Call\Remove_Sole_Value_Sprintf_Rector;
use Rector\Code_Quality\Rector\Func_Call\Set_Type_To_Cast_Rector;
use Rector\Code_Quality\Rector\Func_Call\Simplify_Func_Get_Args_Count_Rector;
use Rector\Code_Quality\Rector\Func_Call\Simplify_In_Array_Values_Rector;
use Rector\Code_Quality\Rector\Func_Call\Simplify_Regex_Pattern_Rector;
use Rector\Code_Quality\Rector\Func_Call\Simplify_Strpos_Lower_Rector;
use Rector\Code_Quality\Rector\Func_Call\Single_In_Array_To_Compare_Rector;
use Rector\Code_Quality\Rector\Func_Call\Sort_Call_Like_Named_Args_Rector;
use Rector\Code_Quality\Rector\Func_Call\Unwrap_Sprintf_One_Argument_Rector;
use Rector\Code_Quality\Rector\Identical\Boolean_Not_Identical_To_Not_Identical_Rector;
use Rector\Code_Quality\Rector\Identical\Flip_Type_Control_To_Use_Exclusive_Type_Rector;
use Rector\Code_Quality\Rector\Identical\Simplify_Array_Search_Rector;
use Rector\Code_Quality\Rector\Identical\Simplify_Bool_Identical_True_Rector;
use Rector\Code_Quality\Rector\Identical\Simplify_Conditions_Rector;
use Rector\Code_Quality\Rector\Identical\Strlen_Zero_To_Identical_Empty_String_Rector;
use Rector\Code_Quality\Rector\If_\Combine_If_Rector;
use Rector\Code_Quality\Rector\If_\Complete_Missing_If_Else_Bracket_Rector;
use Rector\Code_Quality\Rector\If_\Consecutive_Null_Compare_Returns_To_Null_Coalesce_Queue_Rector;
use Rector\Code_Quality\Rector\If_\Explicit_Bool_Compare_Rector;
use Rector\Code_Quality\Rector\If_\Shorten_Else_If_Rector;
use Rector\Code_Quality\Rector\If_\Simplify_If_Else_To_Ternary_Rector;
use Rector\Code_Quality\Rector\If_\Simplify_If_Not_Null_Return_Rector;
use Rector\Code_Quality\Rector\If_\Simplify_If_Nullable_Return_Rector;
use Rector\Code_Quality\Rector\If_\Simplify_If_Return_Bool_Rector;
use Rector\Code_Quality\Rector\Include_\Absolutize_Require_And_Include_Path_Rector;
use Rector\Code_Quality\Rector\Isset_\Isset_On_Property_Object_To_Property_Exists_Rector;
use Rector\Code_Quality\Rector\Logical_And\And_Assigns_To_Separate_Lines_Rector;
use Rector\Code_Quality\Rector\Logical_And\Logical_To_Boolean_Rector;
use Rector\Code_Quality\Rector\New_\New_Static_To_New_Self_Rector;
use Rector\Code_Quality\Rector\Not_Equal\Common_Not_Equal_Rector;
use Rector\Code_Quality\Rector\Nullsafe_Method_Call\Cleanup_Unneeded_Nullsafe_Operator_Rector;
use Rector\Code_Quality\Rector\Switch_\Singular_Switch_To_If_Rector;
use Rector\Code_Quality\Rector\Switch_\Switch_True_To_If_Rector;
use Rector\Code_Quality\Rector\Ternary\Array_Key_Exists_Ternary_Then_Value_To_Coalescing_Rector;
use Rector\Code_Quality\Rector\Ternary\Number_Compare_To_Max_Func_Call_Rector;
use Rector\Code_Quality\Rector\Ternary\Simplify_Tautology_Ternary_Rector;
use Rector\Code_Quality\Rector\Ternary\Switch_Negated_Ternary_Rector;
use Rector\Code_Quality\Rector\Ternary\Ternary_Empty_Array_Array_Dim_Fetch_To_Coalesce_Rector;
use Rector\Code_Quality\Rector\Ternary\Ternary_Implode_To_Implode_Rector;
use Rector\Code_Quality\Rector\Ternary\Unnecessary_Ternary_Expression_Rector;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Php52\Rector\Property\Var_To_Public_Property_Rector;
use Rector\Php71\Rector\Func_Call\Remove_Extra_Parameters_Rector;
use Rector\Renaming\Rector\Func_Call\Rename_Function_Rector;
use Rector\Strict\Rector\Empty_\Disallowed_Empty_Rule_Fixer_Rector;
/**
 * Key 0 = level 0
 * Key 50 = level 50
 *
 * Start at 0, go slowly higher, one level per PR, and improve your rule coverage
 *
 * From the safest rules to more changing ones.
 *
 * This list can change in time, based on community feedback,
 * what rules are safer than others. The safest rules will be always in the top.
 */
final class Code_Quality_Level
{
    /**
     * The rule order matters, as it's used in the withCodeQualityLevel() method
     * Place the safest rules first, follow by more complex ones
     *
     * @var array<class-string<RectorInterface>>
     */
    public const RULES = [Combined_Assign_Rector::class, Simplify_Empty_Array_Check_Rector::class, Replace_Multiple_Boolean_Not_Rector::class, Replace_Constant_Boolean_Not_Rector::class, Foreach_To_In_Array_Rector::class, Repeated_Or_Equal_To_In_Array_Rector::class, Repeated_And_Not_Equal_To_Not_In_Array_Rector::class, Simplify_Foreach_To_Coalescing_Rector::class, Simplify_Func_Get_Args_Count_Rector::class, Simplify_In_Array_Values_Rector::class, Simplify_Strpos_Lower_Rector::class, Simplify_Array_Search_Rector::class, Simplify_Conditions_Rector::class, Simplify_If_Not_Null_Return_Rector::class, Simplify_If_Return_Bool_Rector::class, Unnecessary_Ternary_Expression_Rector::class, Remove_Extra_Parameters_Rector::class, Simplify_De_Morgan_Binary_Rector::class, Simplify_Tautology_Ternary_Rector::class, Single_In_Array_To_Compare_Rector::class, Simplify_If_Else_To_Ternary_Rector::class, Ternary_Implode_To_Implode_Rector::class, Join_String_Concat_Rector::class, Consecutive_Null_Compare_Returns_To_Null_Coalesce_Queue_Rector::class, Explicit_Bool_Compare_Rector::class, Combine_If_Rector::class, Use_Identical_Over_Equal_With_Same_Type_Rector::class, Simplify_Bool_Identical_True_Rector::class, Simplify_Regex_Pattern_Rector::class, Boolean_Not_Identical_To_Not_Identical_Rector::class, And_Assigns_To_Separate_Lines_Rector::class, Compact_To_Variables_Rector::class, Complete_Dynamic_Properties_Rector::class, Is_A_With_String_With_Third_Argument_Rector::class, Strlen_Zero_To_Identical_Empty_String_Rector::class, Throw_With_Previous_Exception_Rector::class, Remove_Sole_Value_Sprintf_Rector::class, Shorten_Else_If_Rector::class, Explicit_Return_Null_Rector::class, Array_Merge_Of_Non_Arrays_To_Simple_Array_Rector::class, Array_Key_Exists_Ternary_Then_Value_To_Coalescing_Rector::class, Absolutize_Require_And_Include_Path_Rector::class, Change_Array_Push_To_Array_Assign_Rector::class, For_Repeated_Count_To_Own_Variable_Rector::class, Foreach_Items_Assign_To_Empty_Array_To_Assign_Rector::class, Inline_If_To_Explicit_If_Rector::class, Unused_Foreach_Value_To_Array_Keys_Rector::class, Common_Not_Equal_Rector::class, Set_Type_To_Cast_Rector::class, Logical_To_Boolean_Rector::class, Var_To_Public_Property_Rector::class, Isset_On_Property_Object_To_Property_Exists_Rector::class, New_Static_To_New_Self_Rector::class, Unwrap_Sprintf_One_Argument_Rector::class, Variable_Const_Fetch_To_Class_Const_Fetch_Rector::class, Switch_Negated_Ternary_Rector::class, Singular_Switch_To_If_Rector::class, Simplify_If_Nullable_Return_Rector::class, Call_User_Func_With_Arrow_Function_To_Inline_Rector::class, Flip_Type_Control_To_Use_Exclusive_Type_Rector::class, Inline_Array_Return_Assign_Rector::class, Inline_Is_A_Instance_Of_Rector::class, Ternary_False_Expression_To_If_Rector::class, Inline_Constructor_Default_To_Property_Rector::class, Ternary_Empty_Array_Array_Dim_Fetch_To_Coalesce_Rector::class, Optional_Parameters_After_Required_Rector::class, Simplify_Empty_Check_On_Empty_Array_Rector::class, Switch_True_To_If_Rector::class, Cleanup_Unneeded_Nullsafe_Operator_Rector::class, Disallowed_Empty_Rule_Fixer_Rector::class, Locally_Called_Static_Method_To_Non_Static_Rector::class, Number_Compare_To_Max_Func_Call_Rector::class, Complete_Missing_If_Else_Bracket_Rector::class, Remove_Useless_Is_Object_Check_Rector::class, Convert_Static_To_Self_Rector::class, Sort_Call_Like_Named_Args_Rector::class, Sort_Attribute_Named_Args_Rector::class, Remove_Readonly_Property_Visibility_On_Readonly_Class_Rector::class];
    /**
     * @var array<class-string<RectorInterface>, mixed[]>
     */
    public const RULES_WITH_CONFIGURATION = [Rename_Function_Rector::class => [
        'split' => 'explode',
        'join' => 'implode',
        'sizeof' => 'count',
        # https://www.php.net/manual/en/aliases.php
        'chop' => 'rtrim',
        'doubleval' => 'floatval',
        'gzputs' => 'gzwrite',
        'fputs' => 'fwrite',
        'ini_alter' => 'ini_set',
        'is_double' => 'is_float',
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_writeable' => 'is_writable',
        'key_exists' => 'array_key_exists',
        'pos' => 'current',
        'strchr' => 'strstr',
        # mb
        'mbstrcut' => 'mb_strcut',
        'mbstrlen' => 'mb_strlen',
        'mbstrpos' => 'mb_strpos',
        'mbstrrpos' => 'mb_strrpos',
        'mbsubstr' => 'mb_substr',
    ]];
}