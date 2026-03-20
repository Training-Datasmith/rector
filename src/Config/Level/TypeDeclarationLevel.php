<?php

declare (strict_types=1);
namespace Rector\Config\Level;

use Rector\Code_Quality\Rector\Class_\Return_Iterator_In_Data_Provider_Rector;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Symfony\Code_Quality\Rector\Class_Method\Response_Return_Type_Controller_Action_Rector;
use Rector\Type_Declaration\Rector\Arrow_Function\Add_Arrow_Function_Return_Type_Rector;
use Rector\Type_Declaration\Rector\Class_\Add_Tests_Void_Return_Type_Where_No_Return_Rector;
use Rector\Type_Declaration\Rector\Class_\Child_Doctrine_Repository_Class_Type_Rector;
use Rector\Type_Declaration\Rector\Class_\Merge_Date_Time_Property_Type_Declaration_Rector;
use Rector\Type_Declaration\Rector\Class_\Object_Typed_Property_From_Jms_Serializer_Attribute_Type_Rector;
use Rector\Type_Declaration\Rector\Class_\Property_Type_From_Strict_Setter_Getter_Rector;
use Rector\Type_Declaration\Rector\Class_\Return_Type_From_Strict_Ternary_Rector;
use Rector\Type_Declaration\Rector\Class_\Scalar_Typed_Property_From_Jms_Serializer_Attribute_Type_Rector;
use Rector\Type_Declaration\Rector\Class_\Typed_Property_From_Create_Mock_Assign_Rector;
use Rector\Type_Declaration\Rector\Class_\Typed_Property_From_Docblock_Set_Up_Defined_Rector;
use Rector\Type_Declaration\Rector\Class_\Typed_Static_Property_In_Behat_Context_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Method_Call_Based_Strict_Param_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Param_From_Dim_Fetch_Key_Use_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Param_String_Type_From_Sprintf_Use_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Param_Type_Based_On_Php_Unit_Data_Provider_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Param_Type_From_Property_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Return_Type_Declaration_Based_On_Parent_Class_Method_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Return_Type_From_Try_Catch_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Add_Void_Return_Type_Where_No_Return_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Bool_Return_Type_From_Boolean_Const_Returns_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Bool_Return_Type_From_Boolean_Strict_Returns_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Known_Magic_Class_Method_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Narrow_Object_Return_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Numeric_Return_Type_From_Strict_Returns_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Numeric_Return_Type_From_Strict_Scalar_Returns_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Param_Type_By_Method_Call_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Param_Type_By_Parent_Call_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Never_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Nullable_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Mock_Object_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Return_Cast_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Return_Direct_Array_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Return_New_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Strict_Constant_Return_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Strict_Fluent_Return_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Strict_Native_Call_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Strict_New_Array_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Strict_Param_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Strict_Typed_Call_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Strict_Typed_Property_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Type_From_Symfony_Serializer_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Return_Union_Type_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Strict_Array_Param_Dim_Fetch_Rector;
use Rector\Type_Declaration\Rector\Class_Method\Strict_String_Param_Concat_Rector;
use Rector\Type_Declaration\Rector\Class_Method\String_Return_Type_From_Strict_Scalar_Returns_Rector;
use Rector\Type_Declaration\Rector\Class_Method\String_Return_Type_From_Strict_String_Returns_Rector;
use Rector\Type_Declaration\Rector\Closure\Add_Closure_Never_Return_Type_Rector;
use Rector\Type_Declaration\Rector\Closure\Add_Closure_Void_Return_Type_Where_No_Return_Rector;
use Rector\Type_Declaration\Rector\Closure\Closure_Return_Type_Rector;
use Rector\Type_Declaration\Rector\Empty_\Empty_On_Nullable_Object_To_Instance_Of_Rector;
use Rector\Type_Declaration\Rector\Func_Call\Add_Array_Function_Closure_Param_Type_Rector;
use Rector\Type_Declaration\Rector\Func_Call\Add_Arrow_Function_Param_Array_Where_Dim_Fetch_Rector;
use Rector\Type_Declaration\Rector\Function_\Add_Function_Void_Return_Type_Where_No_Return_Rector;
use Rector\Type_Declaration\Rector\Function_Like\Add_Closure_Param_Type_For_Array_Map_Rector;
use Rector\Type_Declaration\Rector\Function_Like\Add_Closure_Param_Type_For_Array_Reduce_Rector;
use Rector\Type_Declaration\Rector\Function_Like\Add_Closure_Param_Type_From_Iterable_Method_Call_Rector;
use Rector\Type_Declaration\Rector\Function_Like\Add_Param_Type_Spl_Fixed_Array_Rector;
use Rector\Type_Declaration\Rector\Function_Like\Add_Return_Type_Declaration_From_Yields_Rector;
use Rector\Type_Declaration\Rector\Property\Typed_Property_From_Assigns_Rector;
use Rector\Type_Declaration\Rector\Property\Typed_Property_From_Strict_Constructor_Rector;
use Rector\Type_Declaration\Rector\Property\Typed_Property_From_Strict_Set_Up_Rector;
final class Type_Declaration_Level
{
    /**
     * The rule order matters, as its used in withTypeCoverageLevel() method
     * Place the safest rules first, follow by more complex ones
     *
     * @var array<class-string<RectorInterface>>
     */
    public const RULES = [
        // php 7.1, start with closure first, as safest
        Add_Closure_Void_Return_Type_Where_No_Return_Rector::class,
        Add_Function_Void_Return_Type_Where_No_Return_Rector::class,
        Add_Tests_Void_Return_Type_Where_No_Return_Rector::class,
        Return_Iterator_In_Data_Provider_Rector::class,
        Return_Type_From_Mock_Object_Rector::class,
        Typed_Property_From_Create_Mock_Assign_Rector::class,
        Add_Arrow_Function_Return_Type_Rector::class,
        Bool_Return_Type_From_Boolean_Const_Returns_Rector::class,
        Return_Type_From_Strict_New_Array_Rector::class,
        // scalar and array from constant
        Return_Type_From_Strict_Constant_Return_Rector::class,
        String_Return_Type_From_Strict_Scalar_Returns_Rector::class,
        Numeric_Return_Type_From_Strict_Scalar_Returns_Rector::class,
        Bool_Return_Type_From_Boolean_Strict_Returns_Rector::class,
        String_Return_Type_From_Strict_String_Returns_Rector::class,
        Numeric_Return_Type_From_Strict_Returns_Rector::class,
        Return_Type_From_Strict_Ternary_Rector::class,
        Return_Type_From_Return_Direct_Array_Rector::class,
        Response_Return_Type_Controller_Action_Rector::class,
        Return_Type_From_Return_New_Rector::class,
        Return_Type_From_Return_Cast_Rector::class,
        Return_Type_From_Symfony_Serializer_Rector::class,
        Add_Void_Return_Type_Where_No_Return_Rector::class,
        Return_Type_From_Strict_Typed_Property_Rector::class,
        Return_Nullable_Type_Rector::class,
        // php 7.4
        Empty_On_Nullable_Object_To_Instance_Of_Rector::class,
        // php 7.4
        Typed_Property_From_Strict_Constructor_Rector::class,
        Add_Param_Type_Spl_Fixed_Array_Rector::class,
        Add_Return_Type_Declaration_From_Yields_Rector::class,
        Add_Param_Type_Based_On_Php_Unit_Data_Provider_Rector::class,
        Typed_Property_From_Strict_Set_Up_Rector::class,
        Return_Type_From_Strict_Native_Call_Rector::class,
        Add_Return_Type_From_Try_Catch_Type_Rector::class,
        Return_Type_From_Strict_Typed_Call_Rector::class,
        Child_Doctrine_Repository_Class_Type_Rector::class,
        // php native types
        Known_Magic_Class_Method_Type_Rector::class,
        // param
        Add_Method_Call_Based_Strict_Param_Type_Rector::class,
        Param_Type_By_Parent_Call_Type_Rector::class,
        Narrow_Object_Return_Type_Rector::class,
        // multi types (nullable, union)
        Return_Union_Type_Rector::class,
        // closures
        Add_Closure_Never_Return_Type_Rector::class,
        Add_Closure_Param_Type_For_Array_Map_Rector::class,
        Add_Closure_Param_Type_For_Array_Reduce_Rector::class,
        Closure_Return_Type_Rector::class,
        Add_Arrow_Function_Param_Array_Where_Dim_Fetch_Rector::class,
        // more risky rules
        Return_Type_From_Strict_Param_Rector::class,
        Add_Param_Type_From_Property_Type_Rector::class,
        Merge_Date_Time_Property_Type_Declaration_Rector::class,
        Property_Type_From_Strict_Setter_Getter_Rector::class,
        Param_Type_By_Method_Call_Type_Rector::class,
        Typed_Property_From_Assigns_Rector::class,
        Add_Return_Type_Declaration_Based_On_Parent_Class_Method_Rector::class,
        Return_Type_From_Strict_Fluent_Return_Rector::class,
        Return_Never_Type_Rector::class,
        Strict_String_Param_Concat_Rector::class,
        // jms attributes
        Object_Typed_Property_From_Jms_Serializer_Attribute_Type_Rector::class,
        Scalar_Typed_Property_From_Jms_Serializer_Attribute_Type_Rector::class,
        // array parameter from dim fetch assign inside
        Strict_Array_Param_Dim_Fetch_Rector::class,
        Add_Param_From_Dim_Fetch_Key_Use_Rector::class,
        Add_Param_String_Type_From_Sprintf_Use_Rector::class,
        // possibly based on docblocks, but also helpful, intentionally last
        Add_Array_Function_Closure_Param_Type_Rector::class,
        Typed_Property_From_Docblock_Set_Up_Defined_Rector::class,
        Add_Closure_Param_Type_From_Iterable_Method_Call_Rector::class,
        Typed_Static_Property_In_Behat_Context_Rector::class,
    ];
}