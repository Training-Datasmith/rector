<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Naming;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Group_Use;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
use Php_Stan\Analyser\Name_Scope;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Rector\Naming\Naming\Use_Imports_Resolver;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * @see https://github.com/phpstan/phpstan-src/blob/8376548f76e2c845ae047e3010e873015b796818/src/Analyser/NameScope.php#L32
 */
final class Name_Scope_Factory
{
    /**
     * @readonly
     */
    private Use_Imports_Resolver $use_imports_resolver;
    public function __construct(Use_Imports_Resolver $use_imports_resolver)
    {
        $this->use_imports_resolver = $use_imports_resolver;
    }
    public function create_name_scope_from_node_without_template_types(Node $node): Name_Scope
    {
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if ($scope instanceof Scope) {
            $namespace = $scope->get_namespace();
            $class_reflection = $scope->get_class_reflection();
            $class_name = $class_reflection instanceof Class_Reflection ? $class_reflection->get_name() : null;
        } else {
            $namespace = null;
            $class_name = null;
        }
        $uses = $this->use_imports_resolver->resolve();
        $uses_aliases_to_names = $this->resolve_use_names_by_alias($uses);
        return new Name_Scope($namespace, $uses_aliases_to_names, $class_name);
    }
    /**
     * @param array<Use_|GroupUse> $useNodes
     * @return array<string, string>
     */
    private function resolve_use_names_by_alias(array $use_nodes): array
    {
        $use_names_by_alias = [];
        foreach ($use_nodes as $use_node) {
            $prefix = $this->use_imports_resolver->resolve_prefix($use_node);
            foreach ($use_node->uses as $use_use) {
                /** @var UseItem $useUse */
                $alias_name = $use_use->get_alias()->name;
                // uses must be lowercase, as PHPStan lowercases it
                $lowercased_alias_name = strtolower($alias_name);
                $use_names_by_alias[$lowercased_alias_name] = $prefix . $use_use->name->to_string();
            }
        }
        return $use_names_by_alias;
    }
}