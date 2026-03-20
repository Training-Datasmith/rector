<?php

declare (strict_types=1);
namespace Rector\Config\Level;

use Rector\Code_Quality\Rector\Function_Like\Simplify_Useless_Variable_Rector;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Dead_Code\Rector\Array_\Remove_Duplicated_Array_Key_Rector;
use Rector\Dead_Code\Rector\Assign\Remove_Double_Assign_Rector;
use Rector\Dead_Code\Rector\Assign\Remove_Unused_Variable_Assign_Rector;
use Rector\Dead_Code\Rector\Block\Replace_Block_To_Its_Stmts_Rector;
use Rector\Dead_Code\Rector\Boolean_And\Remove_And_True_Rector;
use Rector\Dead_Code\Rector\Cast\Recasting_Removal_Rector;
use Rector\Dead_Code\Rector\Class_Const\Remove_Unused_Private_Class_Constant_Rector;
use Rector\Dead_Code\Rector\Class_Like\Remove_Typed_Property_Non_Mock_Docblock_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Argument_From_Default_Parent_Call_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Empty_Class_Method_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Null_Tag_Value_Node_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Parent_Delegating_Constructor_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Unused_Constructor_Param_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Unused_Private_Method_Parameter_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Unused_Private_Method_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Unused_Promoted_Property_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Unused_Public_Method_Parameter_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Useless_Assign_From_Property_Promotion_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Useless_Param_Tag_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Useless_Return_Expr_In_Construct_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Useless_Return_Tag_Rector;
use Rector\Dead_Code\Rector\Class_Method\Remove_Void_Docblock_From_Magic_Method_Rector;
use Rector\Dead_Code\Rector\Closure\Remove_Unused_Closure_Variable_Use_Rector;
use Rector\Dead_Code\Rector\Concat\Remove_Concat_Autocast_Rector;
use Rector\Dead_Code\Rector\Const_Fetch\Remove_Php_Version_Id_Check_Rector;
use Rector\Dead_Code\Rector\Expression\Remove_Dead_Stmt_Rector;
use Rector\Dead_Code\Rector\Expression\Simplify_Mirror_Assign_Rector;
use Rector\Dead_Code\Rector\For_\Remove_Dead_Continue_Rector;
use Rector\Dead_Code\Rector\For_\Remove_Dead_If_Foreach_For_Rector;
use Rector\Dead_Code\Rector\For_\Remove_Dead_Loop_Rector;
use Rector\Dead_Code\Rector\Foreach_\Remove_Unused_Foreach_Key_Rector;
use Rector\Dead_Code\Rector\Func_Call\Remove_Filter_Var_On_Exact_Type_Rector;
use Rector\Dead_Code\Rector\Function_Like\Narrow_Wide_Union_Return_Type_Rector;
use Rector\Dead_Code\Rector\Function_Like\Remove_Dead_Return_Rector;
use Rector\Dead_Code\Rector\If_\Reduce_Always_False_If_Or_Rector;
use Rector\Dead_Code\Rector\If_\Remove_Always_True_If_Condition_Rector;
use Rector\Dead_Code\Rector\If_\Remove_Dead_If_Block_Rector;
use Rector\Dead_Code\Rector\If_\Remove_Dead_Instance_Of_Rector;
use Rector\Dead_Code\Rector\If_\Remove_Typed_Property_Dead_Instance_Of_Rector;
use Rector\Dead_Code\Rector\If_\Remove_Unused_Non_Empty_Array_Before_Foreach_Rector;
use Rector\Dead_Code\Rector\If_\Simplify_If_Else_With_Same_Content_Rector;
use Rector\Dead_Code\Rector\If_\Unwrap_Future_Compatible_If_Php_Version_Rector;
use Rector\Dead_Code\Rector\Method_Call\Remove_Null_Arg_On_Null_Default_Param_Rector;
use Rector\Dead_Code\Rector\Node\Remove_Non_Existing_Var_Annotation_Rector;
use Rector\Dead_Code\Rector\Plus\Remove_Dead_Zero_And_One_Operation_Rector;
use Rector\Dead_Code\Rector\Property\Remove_Unused_Private_Property_Rector;
use Rector\Dead_Code\Rector\Property\Remove_Useless_Read_Only_Tag_Rector;
use Rector\Dead_Code\Rector\Property\Remove_Useless_Var_Tag_Rector;
use Rector\Dead_Code\Rector\Property_Property\Remove_Null_Property_Initialization_Rector;
use Rector\Dead_Code\Rector\Return_\Remove_Dead_Condition_Above_Return_Rector;
use Rector\Dead_Code\Rector\Static_Call\Remove_Parent_Call_Without_Parent_Rector;
use Rector\Dead_Code\Rector\Stmt\Remove_Condition_Exact_Return_Rector;
use Rector\Dead_Code\Rector\Stmt\Remove_Next_Same_Value_Condition_Rector;
use Rector\Dead_Code\Rector\Stmt\Remove_Unreachable_Statement_Rector;
use Rector\Dead_Code\Rector\Switch_\Remove_Duplicated_Case_In_Switch_Rector;
use Rector\Dead_Code\Rector\Ternary\Ternary_To_Boolean_Or_False_To_Boolean_And_Rector;
use Rector\Dead_Code\Rector\Try_Catch\Remove_Dead_Catch_Rector;
use Rector\Dead_Code\Rector\Try_Catch\Remove_Dead_Try_Catch_Rector;
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
final class Dead_Code_Level
{
    /**
     * Mind that return type declarations are the safest to add,
     * followed by property, then params
     *
     * @var array<class-string<RectorInterface>>
     */
    public const RULES = [
        // easy picks
        Remove_Unused_Foreach_Key_Rector::class,
        Remove_Duplicated_Array_Key_Rector::class,
        Recasting_Removal_Rector::class,
        Remove_And_True_Rector::class,
        Simplify_Mirror_Assign_Rector::class,
        Remove_Dead_Continue_Rector::class,
        Remove_Unused_Non_Empty_Array_Before_Foreach_Rector::class,
        Remove_Null_Property_Initialization_Rector::class,
        Remove_Useless_Return_Expr_In_Construct_Rector::class,
        Replace_Block_To_Its_Stmts_Rector::class,
        Remove_Filter_Var_On_Exact_Type_Rector::class,
        Remove_Typed_Property_Dead_Instance_Of_Rector::class,
        Ternary_To_Boolean_Or_False_To_Boolean_And_Rector::class,
        Remove_Double_Assign_Rector::class,
        Remove_Useless_Assign_From_Property_Promotion_Rector::class,
        Remove_Concat_Autocast_Rector::class,
        Simplify_If_Else_With_Same_Content_Rector::class,
        Remove_Next_Same_Value_Condition_Rector::class,
        Simplify_Useless_Variable_Rector::class,
        Remove_Dead_Zero_And_One_Operation_Rector::class,
        // docblock
        Remove_Void_Docblock_From_Magic_Method_Rector::class,
        Remove_Useless_Param_Tag_Rector::class,
        Remove_Useless_Return_Tag_Rector::class,
        Remove_Useless_Read_Only_Tag_Rector::class,
        Remove_Non_Existing_Var_Annotation_Rector::class,
        Remove_Useless_Var_Tag_Rector::class,
        // prioritize safe belt on RemoveUseless*TagRector that registered previously first
        Remove_Null_Tag_Value_Node_Rector::class,
        Remove_Php_Version_Id_Check_Rector::class,
        Remove_Typed_Property_Non_Mock_Docblock_Rector::class,
        Remove_Always_True_If_Condition_Rector::class,
        Reduce_Always_False_If_Or_Rector::class,
        Remove_Unused_Private_Class_Constant_Rector::class,
        Remove_Unused_Private_Property_Rector::class,
        Remove_Unused_Closure_Variable_Use_Rector::class,
        Remove_Duplicated_Case_In_Switch_Rector::class,
        Remove_Dead_Instance_Of_Rector::class,
        Remove_Dead_Catch_Rector::class,
        Remove_Dead_Try_Catch_Rector::class,
        Remove_Dead_If_Block_Rector::class,
        Remove_Dead_If_Foreach_For_Rector::class,
        Remove_Condition_Exact_Return_Rector::class,
        Remove_Dead_Stmt_Rector::class,
        Unwrap_Future_Compatible_If_Php_Version_Rector::class,
        Remove_Parent_Call_Without_Parent_Rector::class,
        Remove_Parent_Delegating_Constructor_Rector::class,
        Remove_Dead_Condition_Above_Return_Rector::class,
        Remove_Dead_Loop_Rector::class,
        // removing methods could be risky if there is some magic loading them
        Remove_Unused_Promoted_Property_Rector::class,
        Remove_Unused_Private_Method_Parameter_Rector::class,
        Remove_Unused_Public_Method_Parameter_Rector::class,
        Remove_Unused_Private_Method_Rector::class,
        Remove_Unreachable_Statement_Rector::class,
        Remove_Unused_Variable_Assign_Rector::class,
        // this could break framework magic autowiring in some cases
        Remove_Unused_Constructor_Param_Rector::class,
        Remove_Empty_Class_Method_Rector::class,
        Remove_Dead_Return_Rector::class,
        Remove_Argument_From_Default_Parent_Call_Rector::class,
        Remove_Null_Arg_On_Null_Default_Param_Rector::class,
        Narrow_Wide_Union_Return_Type_Rector::class,
    ];
}