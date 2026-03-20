<?php

declare (strict_types=1);
namespace Rector\Config\Level;

use Rector\Coding_Style\Rector\Assign\Split_Double_Assign_Rector;
use Rector\Coding_Style\Rector\Catch_\Catch_Exception_Name_Matching_Type_Rector;
use Rector\Coding_Style\Rector\Class_Const\Remove_Final_From_Const_Rector;
use Rector\Coding_Style\Rector\Class_Const\Split_Grouped_Class_Constants_Rector;
use Rector\Coding_Style\Rector\Class_Like\Newline_Between_Class_Like_Stmts_Rector;
use Rector\Coding_Style\Rector\Class_Method\Binary_Op_Standalone_Assigns_To_Direct_Rector;
use Rector\Coding_Style\Rector\Class_Method\Func_Get_Args_To_Variadic_Param_Rector;
use Rector\Coding_Style\Rector\Class_Method\Make_Inherited_Method_Visibility_Same_As_Parent_Rector;
use Rector\Coding_Style\Rector\Class_Method\Newline_Before_New_Assign_Set_Rector;
use Rector\Coding_Style\Rector\Encapsed\Encapsed_Strings_To_Sprintf_Rector;
use Rector\Coding_Style\Rector\Encapsed\Wrap_Encapsed_Variable_In_Curly_Braces_Rector;
use Rector\Coding_Style\Rector\Func_Call\Call_User_Func_Array_To_Variadic_Rector;
use Rector\Coding_Style\Rector\Func_Call\Call_User_Func_To_Method_Call_Rector;
use Rector\Coding_Style\Rector\Func_Call\Consistent_Implode_Rector;
use Rector\Coding_Style\Rector\Func_Call\Count_Array_To_Empty_Array_Comparison_Rector;
use Rector\Coding_Style\Rector\Func_Call\Strict_Array_Search_Rector;
use Rector\Coding_Style\Rector\Func_Call\Strict_In_Array_Rector;
use Rector\Coding_Style\Rector\Func_Call\Version_Compare_Func_Call_To_Constant_Rector;
use Rector\Coding_Style\Rector\If_\Nullable_Compare_To_Null_Rector;
use Rector\Coding_Style\Rector\Property\Split_Grouped_Properties_Rector;
use Rector\Coding_Style\Rector\Stmt\Newline_After_Statement_Rector;
use Rector\Coding_Style\Rector\Stmt\Remove_Useless_Alias_In_Use_Statement_Rector;
use Rector\Coding_Style\Rector\String_\Simplify_Quote_Escape_Rector;
use Rector\Coding_Style\Rector\String_\Use_Class_Keyword_For_Class_Name_Resolution_Rector;
use Rector\Coding_Style\Rector\Ternary\Ternary_Condition_Variable_Assignment_Rector;
use Rector\Coding_Style\Rector\Use_\Separate_Multi_Use_Imports_Rector;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Php55\Rector\String_\String_Class_Name_To_Class_Constant_Rector;
use Rector\Transform\Rector\Func_Call\Func_Call_To_Const_Fetch_Rector;
use Rector\Visibility\Rector\Class_Method\Explicit_Public_Class_Method_Rector;
/**
 * Key 0 = level 0
 * Key 50 = level 50
 *
 * Start at 0, go slowly higher, one level per PR, and improve your rule coverage
 *
 * From the safest rules to more changing ones.
 *
 * This list can change in time, based on community feedback,
 * what rules are safer than other. The safest rules will be always in the top.
 */
final class Coding_Style_Level
{
    /**
     * The rule order matters, as its used in withCodingStyleLevel() method
     * Place the safest rules first, followed by more complex ones
     *
     * @var array<class-string<RectorInterface>>
     */
    public const RULES = [Separate_Multi_Use_Imports_Rector::class, Newline_Between_Class_Like_Stmts_Rector::class, Newline_After_Statement_Rector::class, Remove_Final_From_Const_Rector::class, Nullable_Compare_To_Null_Rector::class, Consistent_Implode_Rector::class, Ternary_Condition_Variable_Assignment_Rector::class, Simplify_Quote_Escape_Rector::class, String_Class_Name_To_Class_Constant_Rector::class, Catch_Exception_Name_Matching_Type_Rector::class, Split_Double_Assign_Rector::class, Encapsed_Strings_To_Sprintf_Rector::class, Wrap_Encapsed_Variable_In_Curly_Braces_Rector::class, Newline_Before_New_Assign_Set_Rector::class, Make_Inherited_Method_Visibility_Same_As_Parent_Rector::class, Call_User_Func_Array_To_Variadic_Rector::class, Version_Compare_Func_Call_To_Constant_Rector::class, Count_Array_To_Empty_Array_Comparison_Rector::class, Call_User_Func_To_Method_Call_Rector::class, Func_Get_Args_To_Variadic_Param_Rector::class, Strict_Array_Search_Rector::class, Strict_In_Array_Rector::class, Use_Class_Keyword_For_Class_Name_Resolution_Rector::class, Split_Grouped_Properties_Rector::class, Split_Grouped_Class_Constants_Rector::class, Explicit_Public_Class_Method_Rector::class, Remove_Useless_Alias_In_Use_Statement_Rector::class, Binary_Op_Standalone_Assigns_To_Direct_Rector::class];
    /**
     * @var array<class-string<RectorInterface>, mixed[]>
     */
    public const RULES_WITH_CONFIGURATION = [Func_Call_To_Const_Fetch_Rector::class => ['php_sapi_name' => 'PHP_SAPI', 'pi' => 'M_PI']];
}