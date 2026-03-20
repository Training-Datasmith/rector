<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Call_Like;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Nullsafe_Method_Call;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Name;
use Php_Parser\Node\Param;
use Php_Parser\Node\Property_Item;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Const_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node\Stmt\Trait_;
use Php_Stan\Analyser\Scope;
use Rector\Coding_Style\Naming\Class_Naming;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Analyzer\Call_Analyzer;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Value_Object\Method_Name;
final class Node_Name_Resolver
{
    /**
     * @readonly
     */
    private Class_Naming $class_naming;
    /**
     * @readonly
     */
    private Call_Analyzer $call_analyzer;
    /**
     * @var NodeNameResolverInterface[]
     * @readonly
     */
    private iterable $node_name_resolvers = [];
    /**
     * Used to check if a string might contain a regex or fnmatch pattern
     * @var mixed[]
     */
    private const REGEX_WILDCARD_CHARS = ['*', '#', '~', '/'];
    /**
     * @var array<string, NodeNameResolverInterface|null>
     */
    private array $node_name_resolvers_by_class = [];
    /**
     * @param NodeNameResolverInterface[] $nodeNameResolvers
     */
    public function __construct(Class_Naming $class_naming, Call_Analyzer $call_analyzer, iterable $node_name_resolvers = [])
    {
        $this->class_naming = $class_naming;
        $this->call_analyzer = $call_analyzer;
        $this->node_name_resolvers = $node_name_resolvers;
    }
    /**
     * @param string[] $names
     */
    public function is_names(Node $node, array $names): bool
    {
        $node_name = $this->get_name($node);
        if ($node_name === null) {
            return \false;
        }
        foreach ($names as $name) {
            if ($this->is_string_name($node_name, $name)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param Node|Node[] $node
     * @param MethodName::*|string $name
     */
    public function is_name($node, string $name): bool
    {
        $nodes = is_array($node) ? $node : [$node];
        foreach ($nodes as $node) {
            if ($this->is_single_name($node, $name)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Some nodes have always-known string name. This makes PHPStan smarter.
     * @see https://phpstan.org/writing-php-code/phpdoc-types#conditional-return-types
     *
     * @return ($node is Param ? string :
     *  ($node is ClassMethod ? string :
     *  ($node is Property ? string :
     *  ($node is PropertyItem ? string :
     *  ($node is Trait_ ? string :
     *  ($node is Interface_ ? string :
     *  ($node is Const_ ? string :
     *  ($node is Node\Const_ ? string :
     *  ($node is Name ? string :
     *      string|null )))))))))
     * @param \PhpParser\Node|string $node
     */
    public function get_name($node): ?string
    {
        if (is_string($node)) {
            return $node;
        }
        // useful for looped imported names
        $namespaced_name = $node->get_attribute(Attribute_Key::NAMESPACED_NAME);
        if (is_string($namespaced_name)) {
            return $namespaced_name;
        }
        if (($node instanceof Method_Call || $node instanceof Static_Call || $node instanceof Nullsafe_Method_Call) && $this->is_call_or_identifier($node->name)) {
            return null;
        }
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        $resolved_name = $this->resolve_node_name($node, $scope);
        if ($resolved_name !== null) {
            return $resolved_name;
        }
        // more complex
        if (!property_exists($node, 'name')) {
            return null;
        }
        // unable to resolve
        if ($node->name instanceof Expr) {
            return null;
        }
        return (string) $node->name;
    }
    /**
     * @api
     */
    public function are_names_equal(Node $first_node, Node $second_node): bool
    {
        $second_resolved_name = $this->get_name($second_node);
        if ($second_resolved_name === null) {
            return \false;
        }
        return $this->is_name($first_node, $second_resolved_name);
    }
    /**
     * @api
     *
     * @param Name[]|Node[] $nodes
     * @return string[]
     */
    public function get_names(array $nodes): array
    {
        $names = [];
        foreach ($nodes as $node) {
            $name = $this->get_name($node);
            if (!is_string($name)) {
                throw new Should_Not_Happen_Exception();
            }
            $names[] = $name;
        }
        return $names;
    }
    /**
     * @param string|\PhpParser\Node\Name|\PhpParser\Node\Identifier|\PhpParser\Node\Stmt\ClassLike $name
     */
    public function get_short_name($name): string
    {
        return $this->class_naming->get_short_name($name);
    }
    public function is_string_name(string $resolved_name, string $desired_name): bool
    {
        if ($desired_name === '') {
            return \false;
        }
        // special case
        if ($desired_name === 'Object') {
            return $desired_name === $resolved_name;
        }
        if (strcasecmp($resolved_name, $desired_name) === 0) {
            return \true;
        }
        foreach (self::REGEX_WILDCARD_CHARS as $char) {
            if (strpos($desired_name, $char) !== \false) {
                throw new Should_Not_Happen_Exception('Matching of regular expressions is no longer supported. Use $this->getName() and compare with e.g. str_ends_with() or str_starts_with() instead.');
            }
        }
        return \false;
    }
    /**
     * @param \PhpParser\Node\Expr|\PhpParser\Node\Identifier $node
     */
    private function is_call_or_identifier(\Php_Parser\Node $node): bool
    {
        if ($node instanceof Expr) {
            return $this->call_analyzer->is_object_call($node);
        }
        return \true;
    }
    private function is_single_name(Node $node, string $desired_name): bool
    {
        if ($node instanceof Call_Like && !$node instanceof Func_Call) {
            // method call cannot have a name, only the variable or method name
            return \false;
        }
        $resolved_name = $this->get_name($node);
        if ($resolved_name === null) {
            return \false;
        }
        return $this->is_string_name($resolved_name, $desired_name);
    }
    private function resolve_node_name(Node $node, ?Scope $scope): ?string
    {
        $node_class = get_class($node);
        if (array_key_exists($node_class, $this->node_name_resolvers_by_class)) {
            $resolver = $this->node_name_resolvers_by_class[$node_class];
            if ($resolver instanceof Node_Name_Resolver_Interface) {
                return $resolver->resolve($node, $scope);
            }
            return null;
        }
        foreach ($this->node_name_resolvers as $node_name_resolver) {
            if (!\is_a($node, $node_name_resolver->get_node(), \true)) {
                continue;
            }
            $this->node_name_resolvers_by_class[$node_class] = $node_name_resolver;
            return $node_name_resolver->resolve($node, $scope);
        }
        $this->node_name_resolvers_by_class[$node_class] = null;
        return null;
    }
}