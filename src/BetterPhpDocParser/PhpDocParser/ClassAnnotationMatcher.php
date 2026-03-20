<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Parser;

use Php_Parser\Node;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Stmt\Group_Use;
use Php_Parser\Node\Stmt\Use_;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Reflection_Provider;
use Rector\Coding_Style\Node_Analyzer\Use_Import_Name_Matcher;
use Rector\Naming\Naming\Use_Imports_Resolver;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * Matches "@ORM\Entity" to FQN names based on use imports in the file
 */
final class Class_Annotation_Matcher
{
    /**
     * @readonly
     */
    private Use_Import_Name_Matcher $use_import_name_matcher;
    /**
     * @readonly
     */
    private Use_Imports_Resolver $use_imports_resolver;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    /**
     * @var array<non-empty-string, string>
     */
    private array $fully_qualified_name_by_hash = [];
    public function __construct(Use_Import_Name_Matcher $use_import_name_matcher, Use_Imports_Resolver $use_imports_resolver, Reflection_Provider $reflection_provider)
    {
        $this->use_import_name_matcher = $use_import_name_matcher;
        $this->use_imports_resolver = $use_imports_resolver;
        $this->reflection_provider = $reflection_provider;
    }
    /**
     * @return non-empty-string
     */
    public function resolve_tag_fully_qualified_name(string $tag, Node $node): string
    {
        $unique_id = $tag . spl_object_id($node);
        if (isset($this->fully_qualified_name_by_hash[$unique_id])) {
            return $this->fully_qualified_name_by_hash[$unique_id];
        }
        $tag = ltrim($tag, '@');
        $uses = $this->use_imports_resolver->resolve();
        $fully_qualified_class = $this->resolve_fully_qualified_class($uses, $node, $tag);
        if ($fully_qualified_class === null) {
            $fully_qualified_class = $tag;
        }
        $this->fully_qualified_name_by_hash[$unique_id] = $fully_qualified_class;
        return $fully_qualified_class;
    }
    /**
     * @param array<Use_|GroupUse> $uses
     * @return non-empty-string|null
     */
    private function resolve_fully_qualified_class(array $uses, Node $node, string $tag): ?string
    {
        $scope = $node->get_attribute(Attribute_Key::SCOPE);
        if ($scope instanceof Scope) {
            $namespace = $scope->get_namespace();
            if ($namespace !== null) {
                $namespaced_tag = $namespace . '\\' . $tag;
                if ($this->reflection_provider->has_class($namespaced_tag)) {
                    return $namespaced_tag;
                }
                if (strpos($tag, '\\') === \false) {
                    return $this->resolve_as_aliased($uses, $tag);
                }
                if ($this->is_preslashed_existing_class($tag)) {
                    // Global or absolute Class
                    return $tag;
                }
            }
        }
        return $this->use_import_name_matcher->match_name_with_uses($tag, $uses);
    }
    /**
     * @param array<Use_|GroupUse> $uses
     * @return non-empty-string|null
     */
    private function resolve_as_aliased(array $uses, string $tag): ?string
    {
        foreach ($uses as $use) {
            $prefix = $this->use_imports_resolver->resolve_prefix($use);
            foreach ($use->uses as $use_use) {
                if (!$use_use->alias instanceof Identifier) {
                    continue;
                }
                if ($use_use->alias->to_string() === $tag) {
                    return $prefix . $use_use->name->to_string();
                }
            }
        }
        return $this->use_import_name_matcher->match_name_with_uses($tag, $uses);
    }
    private function is_preslashed_existing_class(string $tag): bool
    {
        if (strncmp($tag, '\\', strlen('\\')) !== 0) {
            return \false;
        }
        return $this->reflection_provider->has_class($tag);
    }
}