<?php

declare (strict_types=1);
namespace Rector\Config\Level;

use Rector\Contract\Rector\Rector_Interface;
use Rector\Type_Declaration\Rector\Class_Method\Add_Param_Array_Docblock_Based_On_Callable_Native_Func_Call_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Return_Array_Docblock_Based_On_Array_Map_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Return_Docblock_For_Scalar_Array_From_Assigns_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_\Add_Return_Docblock_Data_Provider_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_\Add_Var_Array_Docblock_From_Dim_Fetch_Assign_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_\Class_Method_Array_Docblock_Param_From_Local_Calls_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_\Docblock_Var_Array_From_Getter_Return_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_\Docblock_Var_Array_From_Property_Defaults_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_\Docblock_Var_From_Param_Docblock_In_Constructor_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Param_Array_Docblock_Based_On_Array_Map_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Param_Array_Docblock_From_Assigns_Param_To_Param_Reference_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Param_Array_Docblock_From_Data_Provider_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Param_Array_Docblock_From_Dim_Fetch_Access_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Return_Docblock_For_Array_Dim_Assigned_Object_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Return_Docblock_For_Common_Object_Denominator_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Return_Docblock_For_Dim_Fetch_Array_From_Assigns_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Add_Return_Docblock_For_Json_Array_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Docblock_Getter_Return_Array_From_Property_Docblock_Var_Rector;
use Rector\Type_Declaration_Docblocks\Rector\Class_Method\Docblock_Return_Array_From_Direct_Array_Instance_Rector;
final class Type_Declaration_Docblocks_Level
{
    /**
     * @var array<class-string<RectorInterface>>
     */
    public const RULES = [
        // start with rules based on native code
        // property var
        Docblock_Var_Array_From_Property_Defaults_Rector::class,
        // tests
        Add_Param_Array_Docblock_From_Data_Provider_Rector::class,
        Add_Return_Docblock_Data_Provider_Rector::class,
        // param
        Add_Param_Array_Docblock_From_Dim_Fetch_Access_Rector::class,
        Class_Method_Array_Docblock_Param_From_Local_Calls_Rector::class,
        Add_Param_Array_Docblock_Based_On_Array_Map_Rector::class,
        Add_Param_Array_Docblock_From_Assigns_Param_To_Param_Reference_Rector::class,
        Add_Param_Array_Docblock_Based_On_Callable_Native_Func_Call_Rector::class,
        // return
        Add_Return_Docblock_For_Common_Object_Denominator_Rector::class,
        Add_Return_Array_Docblock_Based_On_Array_Map_Rector::class,
        Add_Return_Docblock_For_Scalar_Array_From_Assigns_Rector::class,
        Docblock_Return_Array_From_Direct_Array_Instance_Rector::class,
        Add_Return_Docblock_For_Array_Dim_Assigned_Object_Rector::class,
        Add_Return_Docblock_For_Json_Array_Rector::class,
        // move to rules based on existing docblocks, as more risky
        // property var
        Docblock_Var_From_Param_Docblock_In_Constructor_Rector::class,
        Docblock_Var_Array_From_Getter_Return_Rector::class,
        Add_Var_Array_Docblock_From_Dim_Fetch_Assign_Rector::class,
        // return
        Docblock_Getter_Return_Array_From_Property_Docblock_Var_Rector::class,
        // run latter after other rules, as more generic
        Add_Return_Docblock_For_Dim_Fetch_Array_From_Assigns_Rector::class,
    ];
}