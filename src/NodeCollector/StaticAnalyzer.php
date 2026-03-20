<?php

declare (strict_types=1);
namespace Rector\Node_Collector;

use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Stan\Php_Doc\Resolved_Php_Doc_Block;
use Php_Stan\Reflection\Class_Reflection;
use Rector\Util\String_Utils;
final class Static_Analyzer
{
    public function is_static_method(Class_Reflection $class_reflection, string $method_name, ?Class_ $class = null): bool
    {
        if ($class_reflection->has_native_method($method_name)) {
            $extended_method_reflection = $class_reflection->get_native_method($method_name);
            if ($extended_method_reflection->is_static()) {
                // use cached ClassReflection
                if (!$class instanceof Class_) {
                    return \true;
                }
                // use non-cached Class_
                $class_method = $class->get_method($method_name);
                if ($class_method instanceof Class_Method && $class_method->is_static()) {
                    return \true;
                }
            }
        }
        // could be static in doc type magic
        // @see https://regex101.com/r/tlvfTB/1
        return $this->has_static_annotation($method_name, $class_reflection);
    }
    private function has_static_annotation(string $method_name, Class_Reflection $class_reflection): bool
    {
        $resolved_php_doc_block = $class_reflection->get_resolved_php_doc();
        if (!$resolved_php_doc_block instanceof Resolved_Php_Doc_Block) {
            return \false;
        }
        // @see https://regex101.com/r/7Zkej2/1
        return String_Utils::is_match($resolved_php_doc_block->get_php_doc_string(), '#@method\s*static\s*((([\w\|\\\\]+)|\$this)*+(\[\])*)*\s+\b' . $method_name . 'b#');
    }
}