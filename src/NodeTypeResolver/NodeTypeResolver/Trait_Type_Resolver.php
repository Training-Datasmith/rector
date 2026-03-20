<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Trait_;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
/**
 * @see \Rector\Tests\NodeTypeResolver\PerNodeTypeResolver\TraitTypeResolver\TraitTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Trait_>
 */
final class Trait_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Reflection_Provider $reflection_provider)
    {
        $this->reflection_provider = $reflection_provider;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Trait_::class];
    }
    /**
     * @param Trait_ $node
     */
    public function resolve(Node $node): Type
    {
        $trait_name = (string) $node->namespaced_name;
        if (!$this->reflection_provider->has_class($trait_name)) {
            return new Mixed_Type();
        }
        $class_reflection = $this->reflection_provider->get_class($trait_name);
        $types = [];
        $types[] = new Object_Type($trait_name);
        foreach ($class_reflection->get_traits() as $used_trait_reflection) {
            $types[] = new Object_Type($used_trait_reflection->get_name());
        }
        if (count($types) === 1) {
            return $types[0];
        }
        return new Union_Type($types);
    }
}