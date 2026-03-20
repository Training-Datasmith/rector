<?php

declare (strict_types=1);
namespace Rector\Family_Tree\Reflection;

use Php_Parser\Node\Name;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
final class Family_Relations_Analyzer
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    public function __construct(Reflection_Provider $reflection_provider, Node_Name_Resolver $node_name_resolver)
    {
        $this->reflection_provider = $reflection_provider;
        $this->node_name_resolver = $node_name_resolver;
    }
    /**
     * @api
     * @return string[]
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Name $classOrName
     */
    public function get_class_like_ancestor_names($class_or_name): array
    {
        $ancestor_names = [];
        if ($class_or_name instanceof Name) {
            $full_name = $this->node_name_resolver->get_name($class_or_name);
            if (!$this->reflection_provider->has_class($full_name)) {
                return [];
            }
            $class_reflection = $this->reflection_provider->get_class($full_name);
            $ancestors = array_merge($class_reflection->get_parents(), $class_reflection->get_interfaces());
            return array_map(static fn(Class_Reflection $class_reflection): string => $class_reflection->get_name(), $ancestors);
        }
        if ($class_or_name instanceof Interface_) {
            foreach ($class_or_name->extends as $extend_interface_name) {
                $ancestor_names[] = $this->node_name_resolver->get_name($extend_interface_name);
                $ancestor_names = array_merge($ancestor_names, $this->get_class_like_ancestor_names($extend_interface_name));
            }
        }
        if ($class_or_name instanceof Class_) {
            if ($class_or_name->extends instanceof Name) {
                $ancestor_names[] = $this->node_name_resolver->get_name($class_or_name->extends);
                $ancestor_names = array_merge($ancestor_names, $this->get_class_like_ancestor_names($class_or_name->extends));
            }
            foreach ($class_or_name->implements as $implement) {
                $ancestor_names[] = $this->node_name_resolver->get_name($implement);
                $ancestor_names = array_merge($ancestor_names, $this->get_class_like_ancestor_names($implement));
            }
        }
        /** @var string[] $ancestorNames */
        return $ancestor_names;
    }
}