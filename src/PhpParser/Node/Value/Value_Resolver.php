<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node\Value;

use ArithmeticError;
use Php_Parser\Const_Expr_Evaluation_Exception;
use Php_Parser\Const_Expr_Evaluator;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Binary_Op\Concat;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Expr\Const_Fetch;
use Php_Parser\Node\Interpolated_String_Part;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar\Magic_Const\Class_;
use Php_Parser\Node\Scalar\Magic_Const\Dir;
use Php_Parser\Node\Scalar\Magic_Const\File;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Constant\Constant_String_Type;
use Php_Stan\Type\Constant_Scalar_Type;
use Php_Stan\Type\Type;
use Rector\Application\Provider\Current_File_Provider;
use Rector\Enum\Object_Reference;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Analyzer\Const_Fetch_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Reflection\Class_Reflection_Analyzer;
use Rector\Reflection\Reflection_Resolver;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
use TypeError;
/**
 * @see \Rector\Tests\PhpParser\Node\Value\ValueResolverTest
 * @todo make use of constant type of $scope->getType()
 */
final class Value_Resolver
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @readonly
     */
    private Const_Fetch_Analyzer $const_fetch_analyzer;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    /**
     * @readonly
     */
    private Class_Reflection_Analyzer $class_reflection_analyzer;
    /**
     * @readonly
     */
    private Current_File_Provider $current_file_provider;
    private ?Const_Expr_Evaluator $const_expr_evaluator = null;
    public function __construct(Node_Name_Resolver $node_name_resolver, Node_Type_Resolver $node_type_resolver, Const_Fetch_Analyzer $const_fetch_analyzer, Reflection_Provider $reflection_provider, Reflection_Resolver $reflection_resolver, Class_Reflection_Analyzer $class_reflection_analyzer, Current_File_Provider $current_file_provider)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->node_type_resolver = $node_type_resolver;
        $this->const_fetch_analyzer = $const_fetch_analyzer;
        $this->reflection_provider = $reflection_provider;
        $this->reflection_resolver = $reflection_resolver;
        $this->class_reflection_analyzer = $class_reflection_analyzer;
        $this->current_file_provider = $current_file_provider;
    }
    /**
     * @param mixed $value
     */
    public function is_value(Expr $expr, $value): bool
    {
        return $this->get_value($expr) === $value;
    }
    /**
     * @param \PhpParser\Node\Arg|\PhpParser\Node\Expr|\PhpParser\Node\InterpolatedStringPart $expr
     * @return mixed
     */
    public function get_value($expr, bool $resolved_class_reference = \false)
    {
        if ($expr instanceof Arg) {
            $expr = $expr->value;
        }
        if ($expr instanceof Concat) {
            return $this->process_concat($expr, $resolved_class_reference);
        }
        if ($expr instanceof Class_Const_Fetch && $resolved_class_reference) {
            $class = $this->node_name_resolver->get_name($expr->class);
            if (in_array($class, [Object_Reference::SELF, Object_Reference::STATIC], \true)) {
                $class_reflection = $this->reflection_resolver->resolve_class_reflection($expr);
                if ($class_reflection instanceof Class_Reflection) {
                    return $class_reflection->get_name();
                }
            }
            if ($this->node_name_resolver->is_name($expr->name, 'class')) {
                return $class;
            }
        }
        $value = $this->resolve_expr_value_for_const($expr);
        if ($value !== null) {
            return $value;
        }
        if ($expr instanceof Const_Fetch) {
            if ($this->is_null($expr)) {
                return null;
            }
            if ($this->is_true($expr)) {
                return \true;
            }
            if ($this->is_false($expr)) {
                return \false;
            }
            return $this->node_name_resolver->get_name($expr);
        }
        $node_static_type = $this->node_type_resolver->get_type($expr);
        return $this->resolve_constant_type($node_static_type);
    }
    /**
     * @api symfony
     * @param mixed[] $expectedValues
     */
    public function is_values(Expr $expr, array $expected_values): bool
    {
        foreach ($expected_values as $expected_value) {
            if ($this->is_value($expr, $expected_value)) {
                return \true;
            }
        }
        return \false;
    }
    public function is_false(Expr $expr): bool
    {
        return $this->const_fetch_analyzer->is_false($expr);
    }
    public function is_true_or_false(Expr $expr): bool
    {
        return $this->const_fetch_analyzer->is_true_or_false($expr);
    }
    public function is_true(Expr $expr): bool
    {
        return $this->const_fetch_analyzer->is_true($expr);
    }
    public function is_null(Expr $expr): bool
    {
        return $this->const_fetch_analyzer->is_null($expr);
    }
    /**
     * @param Expr[]|null[] $nodes
     * @param mixed[] $expectedValues
     */
    public function are_values_equal(array $nodes, array $expected_values): bool
    {
        foreach ($nodes as $i => $node) {
            if (!$node instanceof Expr) {
                return \false;
            }
            if (!$this->is_value($node, $expected_values[$i])) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @param \PhpParser\Node\Expr|\PhpParser\Node\InterpolatedStringPart $expr
     * @return mixed
     */
    private function resolve_expr_value_for_const($expr)
    {
        if ($expr instanceof Interpolated_String_Part) {
            return $expr->value;
        }
        try {
            $const_expr_evaluator = $this->get_const_expr_evaluator();
            return $const_expr_evaluator->evaluate_directly($expr);
        } catch (Const_Expr_Evaluation_Exception|TypeError|ArithmeticError $exception) {
        }
        if ($expr instanceof Class_) {
            $type = $this->node_type_resolver->get_native_type($expr);
            if ($type instanceof Constant_String_Type) {
                return $type->get_value();
            }
        }
        return null;
    }
    private function process_concat(Concat $concat, bool $resolved_class_reference): string
    {
        return $this->get_value($concat->left, $resolved_class_reference) . $this->get_value($concat->right, $resolved_class_reference);
    }
    private function get_const_expr_evaluator(): Const_Expr_Evaluator
    {
        if ($this->const_expr_evaluator instanceof Const_Expr_Evaluator) {
            return $this->const_expr_evaluator;
        }
        $this->const_expr_evaluator = new Const_Expr_Evaluator(function (Expr $expr) {
            if ($expr instanceof Dir) {
                // __DIR__
                return $this->resolve_dir_constant();
            }
            if ($expr instanceof File) {
                // __FILE__
                return $this->resolve_file_constant($expr);
            }
            // resolve "SomeClass::SOME_CONST"
            if ($expr instanceof Class_Const_Fetch && $expr->class instanceof Name) {
                return $this->resolve_class_const_fetch($expr);
            }
            throw new Const_Expr_Evaluation_Exception(sprintf('Expression of type "%s" cannot be evaluated', $expr->get_type()));
        });
        return $this->const_expr_evaluator;
    }
    private function resolve_dir_constant(): string
    {
        $file = $this->current_file_provider->get_file();
        if (!$file instanceof \Rector\Value_Object\Application\File) {
            throw new Should_Not_Happen_Exception();
        }
        return dirname($file->get_file_path());
    }
    private function resolve_file_constant(File $file): string
    {
        $file = $this->current_file_provider->get_file();
        if (!$file instanceof \Rector\Value_Object\Application\File) {
            throw new Should_Not_Happen_Exception();
        }
        return $file->get_file_path();
    }
    /**
     * @return mixed[]|null
     */
    private function extract_constant_array_type_value(Constant_Array_Type $constant_array_type): ?array
    {
        $keys = [];
        foreach ($constant_array_type->get_key_types() as $i => $key_type) {
            $keys[$i] = $key_type->get_value();
        }
        $values = [];
        foreach ($constant_array_type->get_value_types() as $i => $value_type) {
            if ($value_type instanceof Constant_Array_Type) {
                $value = $this->extract_constant_array_type_value($value_type);
            } elseif ($value_type instanceof Constant_Scalar_Type) {
                $value = $value_type->get_value();
            } elseif (Class_Name_From_Object_Type_Resolver::resolve($value_type) !== null) {
                continue;
            } else {
                return null;
            }
            $values[$keys[$i]] = $value;
        }
        return $values;
    }
    /**
     * @return string|mixed
     */
    private function resolve_class_const_fetch(Class_Const_Fetch $class_const_fetch)
    {
        $class = $this->node_name_resolver->get_name($class_const_fetch->class);
        $constant = $this->node_name_resolver->get_name($class_const_fetch->name);
        if ($class === null) {
            throw new Should_Not_Happen_Exception();
        }
        if ($constant === null) {
            throw new Should_Not_Happen_Exception();
        }
        if (in_array($class, [Object_Reference::SELF, Object_Reference::STATIC, Object_Reference::PARENT], \true)) {
            $class = $this->resolve_class_from_self_static_parent($class_const_fetch, $class);
        }
        if ($constant === 'class') {
            return $class;
        }
        $class_constant_reference = $class . '::' . $constant;
        if (defined($class_constant_reference)) {
            return constant($class_constant_reference);
        }
        if (!$this->reflection_provider->has_class($class)) {
            // fallback to constant reference itself, to avoid fatal error
            return $class_constant_reference;
        }
        $class_reflection = $this->reflection_provider->get_class($class);
        if (!$class_reflection->has_constant($constant)) {
            // fallback to constant reference itself, to avoid fatal error
            return $class_constant_reference;
        }
        if ($class_reflection->is_enum()) {
            // fallback to constant reference itself, to avoid fatal error
            return $class_constant_reference;
        }
        $class_constant_reflection = $class_reflection->get_constant($constant);
        $value_expr = $class_constant_reflection->get_value_expr();
        if ($value_expr instanceof Const_Fetch) {
            return $this->resolve_expr_value_for_const($value_expr);
        }
        return $this->get_value($value_expr);
    }
    private function resolve_class_from_self_static_parent(Class_Const_Fetch $class_const_fetch, string $class): string
    {
        // Scope may be loaded too late, so return empty string early
        // it will be resolved on next traverse
        $scope = $class_const_fetch->get_attribute(Attribute_Key::SCOPE);
        if (!$scope instanceof Scope) {
            return '';
        }
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class_const_fetch);
        if (!$class_reflection instanceof Class_Reflection) {
            throw new Should_Not_Happen_Exception('Complete class parent node for to class const fetch, so "self" or "static" references is resolvable to a class name');
        }
        if ($class !== Object_Reference::PARENT) {
            return $class_reflection->get_name();
        }
        if (!$class_reflection->is_class()) {
            throw new Should_Not_Happen_Exception('Complete class parent node for to class const fetch, so "parent" references is resolvable to lookup parent class');
        }
        // ensure parent class name still resolved even not autoloaded
        $parent_class_name = $this->class_reflection_analyzer->resolve_parent_class_name($class_reflection);
        if ($parent_class_name === null) {
            throw new Should_Not_Happen_Exception();
        }
        return $parent_class_name;
    }
    /**
     * @return mixed
     */
    private function resolve_constant_type(Type $constant_type)
    {
        if ($constant_type instanceof Constant_Array_Type) {
            return $this->extract_constant_array_type_value($constant_type);
        }
        if ($constant_type instanceof Constant_Scalar_Type) {
            return $constant_type->get_value();
        }
        return null;
    }
}