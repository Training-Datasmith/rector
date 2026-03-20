<?php

declare (strict_types=1);
namespace Rector\Php_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Name;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Enum_;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Trait_;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Function_Reflection;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Php\Php_Function_Reflection;
use Php_Stan\Reflection\Php\Php_Property_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node_Scope_And_Metadata_Decorator;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Php_Parser\Node\Better_Node_Finder;
use Rector\Php_Parser\Parser\Rector_Parser;
use Rector\Reflection\Method_Reflection_Resolver;
use Rector\Static_Type_Mapper\Resolver\Class_Name_From_Object_Type_Resolver;
use Rector\Value_Object\Method_Name;
use Throwable;
/**
 * The nodes provided by this resolver is for read-only analysis only!
 * They are not part of node tree processed by Rector, so any changes will not make effect in final printed file.
 */
final class Ast_Resolver
{
    /**
     * @readonly
     */
    private Rector_Parser $rector_parser;
    /**
     * @readonly
     */
    private Node_Scope_And_Metadata_Decorator $node_scope_and_metadata_decorator;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @readonly
     */
    private Method_Reflection_Resolver $method_reflection_resolver;
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * Parsing files is very heavy performance, so this will help to leverage it
     * The value can be also null, when no statements could be parsed from the file.
     *
     * @var array<string, Stmt[]|null>
     */
    private array $parsed_file_nodes = [];
    public function __construct(Rector_Parser $rector_parser, Node_Scope_And_Metadata_Decorator $node_scope_and_metadata_decorator, Node_Name_Resolver $node_name_resolver, Reflection_Provider $reflection_provider, Node_Type_Resolver $node_type_resolver, Method_Reflection_Resolver $method_reflection_resolver, Better_Node_Finder $better_node_finder)
    {
        $this->rector_parser = $rector_parser;
        $this->node_scope_and_metadata_decorator = $node_scope_and_metadata_decorator;
        $this->node_name_resolver = $node_name_resolver;
        $this->reflection_provider = $reflection_provider;
        $this->node_type_resolver = $node_type_resolver;
        $this->method_reflection_resolver = $method_reflection_resolver;
        $this->better_node_finder = $better_node_finder;
    }
    /**
     * @api downgrade
     * @return \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Trait_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Stmt\Enum_|null
     */
    public function resolve_class_from_name(string $class_name)
    {
        if (!$this->reflection_provider->has_class($class_name)) {
            return null;
        }
        $class_reflection = $this->reflection_provider->get_class($class_name);
        return $this->resolve_class_from_class_reflection($class_reflection);
    }
    public function resolve_class_method_from_method_reflection(Method_Reflection $method_reflection): ?Class_Method
    {
        $class_reflection = $method_reflection->get_declaring_class();
        $file_name = $class_reflection->get_file_name();
        $nodes = $this->parse_file_name_to_decorated_nodes($file_name);
        $class_like_name = $class_reflection->get_name();
        $method_name = $method_reflection->get_name();
        /** @var ClassMethod|null $classMethod */
        $class_method = null;
        $this->better_node_finder->find_first($nodes, function (Node $node) use ($class_like_name, $method_name, &$class_method): bool {
            if (!$node instanceof Class_Like) {
                return \false;
            }
            if (!$this->node_name_resolver->is_name($node, $class_like_name)) {
                return \false;
            }
            $method = $node->get_method($method_name);
            if ($method instanceof Class_Method) {
                $class_method = $method;
                return \true;
            }
            return \false;
        });
        return $class_method;
    }
    /**
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\New_ $call
     * @return \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|null
     */
    public function resolve_class_method_or_function_from_call($call)
    {
        if ($call instanceof Func_Call) {
            return $this->resolve_function_from_func_call($call);
        }
        return $this->resolve_class_method_from_call($call);
    }
    public function resolve_function_from_function_reflection(Function_Reflection $function_reflection): ?Function_
    {
        if (!$function_reflection instanceof Php_Function_Reflection) {
            return null;
        }
        $file_name = $function_reflection->get_file_name();
        $nodes = $this->parse_file_name_to_decorated_nodes($file_name);
        $function_name = $function_reflection->get_name();
        /** @var Function_|null $functionNode */
        $function_node = $this->better_node_finder->find_first($nodes, function (Node $node) use ($function_name): bool {
            if (!$node instanceof Function_) {
                return \false;
            }
            return $this->node_name_resolver->is_name($node, $function_name);
        });
        return $function_node;
    }
    /**
     * @param class-string $className
     */
    public function resolve_class_method(string $class_name, string $method_name): ?Class_Method
    {
        $method_reflection = $this->method_reflection_resolver->resolve_method_reflection($class_name, $method_name, null);
        if (!$method_reflection instanceof Method_Reflection) {
            return null;
        }
        $class_method = $this->resolve_class_method_from_method_reflection($method_reflection);
        if (!$class_method instanceof Class_Method) {
            return $this->locate_class_method_in_trait($method_name, $method_reflection);
        }
        return $class_method;
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\NullsafeMethodCall|\PhpParser\Node\Expr\New_ $call
     */
    public function resolve_class_method_from_call($call): ?Class_Method
    {
        if ($call instanceof New_) {
            if ($call->class instanceof Class_) {
                return null;
            }
            $class_name = $this->node_name_resolver->get_name($call->class);
            if ($class_name === null) {
                return null;
            }
            return $this->resolve_class_method($class_name, Method_Name::CONSTRUCT);
        }
        $caller_static_type = $call instanceof Method_Call || $call instanceof Nullsafe_Method_Call ? $this->node_type_resolver->get_type($call->var) : $this->node_type_resolver->get_type($call->class);
        $class_name = Class_Name_From_Object_Type_Resolver::resolve($caller_static_type);
        if ($class_name === null) {
            return null;
        }
        $method_name = $this->node_name_resolver->get_name($call->name);
        if ($method_name === null) {
            return null;
        }
        return $this->resolve_class_method($class_name, $method_name);
    }
    /**
     * @return \PhpParser\Node\Stmt\Trait_|\PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Stmt\Enum_|null
     */
    public function resolve_class_from_class_reflection(Class_Reflection $class_reflection)
    {
        if ($class_reflection->is_builtin()) {
            return null;
        }
        $file_name = $class_reflection->get_file_name();
        $stmts = $this->parse_file_name_to_decorated_nodes($file_name);
        $class_name = $class_reflection->get_name();
        /** @var Class_|Trait_|Interface_|Enum_|null $classLike */
        $class_like = $this->better_node_finder->find_first($stmts, function (Node $node) use ($class_name): bool {
            if (!$node instanceof Class_Like) {
                return \false;
            }
            return $this->node_name_resolver->is_name($node, $class_name);
        });
        return $class_like;
    }
    /**
     * @return Trait_[]
     */
    public function parse_class_reflection_traits(Class_Reflection $class_reflection): array
    {
        /** @var ClassReflection[] $classLikes */
        $class_likes = $class_reflection->get_traits(\true);
        $traits = [];
        foreach ($class_likes as $class_like) {
            $file_name = $class_like->get_file_name();
            $nodes = $this->parse_file_name_to_decorated_nodes($file_name);
            $trait_name = $class_like->get_name();
            $trait_node = $this->better_node_finder->find_first($nodes, function (Node $node) use ($trait_name): bool {
                if (!$node instanceof Trait_) {
                    return \false;
                }
                return $this->node_name_resolver->is_name($node, $trait_name);
            });
            if (!$trait_node instanceof Trait_) {
                continue;
            }
            $traits[] = $trait_node;
        }
        return $traits;
    }
    /**
     * @return \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param|null
     */
    public function resolve_property_from_property_reflection(Php_Property_Reflection $php_property_reflection)
    {
        $class_reflection = $php_property_reflection->get_declaring_class();
        $file_name = $class_reflection->get_file_name();
        $nodes = $this->parse_file_name_to_decorated_nodes($file_name);
        if ($nodes === []) {
            return null;
        }
        $native_reflection_property = $php_property_reflection->get_native_reflection();
        $desired_class_name = $class_reflection->get_name();
        $desired_property_name = $native_reflection_property->get_name();
        $property_node = null;
        $this->better_node_finder->find_first($nodes, function (Node $node) use ($desired_class_name, $desired_property_name, &$property_node): bool {
            if (!$node instanceof Class_Like) {
                return \false;
            }
            if (!$this->node_name_resolver->is_name($node, $desired_class_name)) {
                return \false;
            }
            $property = $node->get_property($desired_property_name);
            if ($property instanceof Property) {
                $property_node = $property;
                return \true;
            }
            return \false;
        });
        if ($property_node instanceof Property) {
            return $property_node;
        }
        // promoted property
        return $this->find_promoted_property_by_name($nodes, $desired_class_name, $desired_property_name);
    }
    /**
     * @return Stmt[]
     */
    public function parse_file_name_to_decorated_nodes(?string $file_name): array
    {
        // probably native PHP → un-parseable
        if ($file_name === null) {
            return [];
        }
        if (isset($this->parsed_file_nodes[$file_name])) {
            return $this->parsed_file_nodes[$file_name];
        }
        try {
            $stmts = $this->rector_parser->parse_file($file_name);
        } catch (Throwable $throwable) {
            /**
             * phpstan.phar contains jetbrains/phpstorm-stubs which the code is not downgraded
             * that if read from lower php < 8.1 may cause crash
             *
             * @see https://github.com/rectorphp/rector/issues/8193 on php 8.0
             * @see https://github.com/rectorphp/rector/issues/8145 on php 7.4
             */
            if (strpos($file_name, 'phpstan.phar') !== \false) {
                return [];
            }
            throw $throwable;
        }
        return $this->parsed_file_nodes[$file_name] = $this->node_scope_and_metadata_decorator->decorate_nodes_from_file($file_name, $stmts);
    }
    private function locate_class_method_in_trait(string $method_name, Method_Reflection $method_reflection): ?Class_Method
    {
        $class_reflection = $method_reflection->get_declaring_class();
        $traits = $this->parse_class_reflection_traits($class_reflection);
        /** @var ClassMethod|null $classMethod */
        $class_method = $this->better_node_finder->find_first($traits, function (Node $node) use ($method_name): bool {
            if (!$node instanceof Class_Method) {
                return \false;
            }
            return $this->node_name_resolver->is_name($node, $method_name);
        });
        return $class_method;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function find_promoted_property_by_name(array $stmts, string $desired_class_name, string $desired_property_name): ?Param
    {
        /** @var Param|null $paramNode */
        $param_node = null;
        $this->better_node_finder->find_first($stmts, function (Node $node) use ($desired_class_name, $desired_property_name, &$param_node): bool {
            if (!$node instanceof Class_) {
                return \false;
            }
            if (!$this->node_name_resolver->is_name($node, $desired_class_name)) {
                return \false;
            }
            $construct_class_method = $node->get_method(Method_Name::CONSTRUCT);
            if (!$construct_class_method instanceof Class_Method) {
                return \false;
            }
            foreach ($construct_class_method->get_params() as $param) {
                if (!$param->is_promoted()) {
                    continue;
                }
                if ($this->node_name_resolver->is_name($param, $desired_property_name)) {
                    $param_node = $param;
                    return \true;
                }
            }
            return \false;
        });
        return $param_node;
    }
    private function resolve_function_from_func_call(Func_Call $func_call): ?Function_
    {
        if ($func_call->name instanceof Expr) {
            return null;
        }
        $function_name = new Name((string) $this->node_name_resolver->get_name($func_call));
        if (!$this->reflection_provider->has_function($function_name, null)) {
            return null;
        }
        $function_reflection = $this->reflection_provider->get_function($function_name, null);
        return $this->resolve_function_from_function_reflection($function_reflection);
    }
}