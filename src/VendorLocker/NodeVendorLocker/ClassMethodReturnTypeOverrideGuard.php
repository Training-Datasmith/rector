<?php

declare (strict_types=1);
namespace Rector\Vendor_Locker\Node_Vendor_Locker;

use Php_Parser\Node\Stmt\Class_Method;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Extended_Function_Variant;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Mixed_Type;
use Rector\File_System\File_Path_Helper;
use Rector\Node_Analyzer\Magic_Class_Method_Analyzer;
use Rector\Node_Type_Resolver\Php_Stan\Parameters_Acceptor_Selector_Variants_Wrapper;
use Rector\Reflection\Reflection_Resolver;
use Rector\Vendor_Locker\Parent_Class_Method_Type_Override_Guard;
final class Class_Method_Return_Type_Override_Guard
{
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    /**
     * @readonly
     */
    private Parent_Class_Method_Type_Override_Guard $parent_class_method_type_override_guard;
    /**
     * @readonly
     */
    private File_Path_Helper $file_path_helper;
    /**
     * @readonly
     */
    private Magic_Class_Method_Analyzer $magic_class_method_analyzer;
    public function __construct(Reflection_Resolver $reflection_resolver, Parent_Class_Method_Type_Override_Guard $parent_class_method_type_override_guard, File_Path_Helper $file_path_helper, Magic_Class_Method_Analyzer $magic_class_method_analyzer)
    {
        $this->reflection_resolver = $reflection_resolver;
        $this->parent_class_method_type_override_guard = $parent_class_method_type_override_guard;
        $this->file_path_helper = $file_path_helper;
        $this->magic_class_method_analyzer = $magic_class_method_analyzer;
    }
    public function should_skip_class_method(Class_Method $class_method, Scope $scope): bool
    {
        if ($this->magic_class_method_analyzer->is_unsafe_overridden($class_method)) {
            return \true;
        }
        // except magic check on above, early allow add return type on private method
        if ($class_method->is_private()) {
            return \false;
        }
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class_method);
        if (!$class_reflection instanceof Class_Reflection) {
            return \true;
        }
        if ($class_reflection->is_abstract()) {
            return \true;
        }
        if ($class_reflection->is_interface()) {
            return \true;
        }
        return !$this->is_return_type_change_allowed($class_method, $scope);
    }
    private function is_return_type_change_allowed(Class_Method $class_method, Scope $scope): bool
    {
        // make sure return type is not protected by parent contract
        $parent_class_method_reflection = $this->parent_class_method_type_override_guard->get_parent_class_method($class_method);
        // nothing to check
        if (!$parent_class_method_reflection instanceof Method_Reflection) {
            return !$this->parent_class_method_type_override_guard->has_parent_class_method($class_method);
        }
        $parameters_acceptor = Parameters_Acceptor_Selector_Variants_Wrapper::select($parent_class_method_reflection, $class_method, $scope);
        if ($parameters_acceptor instanceof Extended_Function_Variant && !$parameters_acceptor->get_native_return_type() instanceof Mixed_Type) {
            return \false;
        }
        $class_reflection = $parent_class_method_reflection->get_declaring_class();
        $file_name = $class_reflection->get_file_name();
        // probably internal
        if ($file_name === null) {
            return \false;
        }
        /*
         * Below verify that both current file name and parent file name is not in the /vendor/, if yes, then allowed.
         * This can happen when rector run into /vendor/ directory while child and parent both are there.
         *
         *  @see https://3v4l.org/Rc0RF#v8.0.13
         *
         *     - both in /vendor/ -> allowed
         *     - one of them in /vendor/ -> not allowed
         *     - both not in /vendor/ -> allowed
         */
        /** @var ClassReflection $currentClassReflection */
        $current_class_reflection = $this->reflection_resolver->resolve_class_reflection($class_method);
        /** @var string $currentFileName */
        $current_file_name = $current_class_reflection->get_file_name();
        // child (current)
        $normalized_current_file_name = $this->file_path_helper->normalize_path_and_schema($current_file_name);
        $is_current_in_vendor = strpos($normalized_current_file_name, '/vendor/') !== \false;
        // parent
        $normalized_file_name = $this->file_path_helper->normalize_path_and_schema($file_name);
        $is_parent_in_vendor = strpos($normalized_file_name, '/vendor/') !== \false;
        return $is_current_in_vendor && $is_parent_in_vendor || !$is_current_in_vendor && !$is_parent_in_vendor;
    }
}