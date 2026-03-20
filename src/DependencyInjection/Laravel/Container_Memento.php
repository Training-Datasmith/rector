<?php

declare (strict_types=1);
namespace Rector\Dependency_Injection\Laravel;

use Rector\Util\Reflection\Privates_Accessor;
use Rector_Prefix202603\Illuminate\Container\Container;
/**
 * Helper service to modify Laravel container
 */
final class Container_Memento
{
    /**
     * @api
     * @see https://tomasvotruba.com/blog/removing-service-from-laravel-container-is-not-that-easy
     */
    public static function forget_tag(Container $container, string $tag_to_forget): void
    {
        // 1. forget instances
        $tagged_classes = $container->tagged($tag_to_forget);
        foreach ($tagged_classes as $tagged_class) {
            $container->offsetUnset(get_class($tagged_class));
        }
        // 2. forget tagged references
        $privates_accessor = new Privates_Accessor();
        $privates_accessor->property_closure($container, 'tags', static function (array $tags) use ($tag_to_forget): array {
            unset($tags[$tag_to_forget]);
            return $tags;
        });
    }
    public static function forget_service(Container $container, string $type_to_forget): void
    {
        // 1. remove the service
        $container->offsetUnset($type_to_forget);
        // 2. remove all tagged rules
        $privates_accessor = new Privates_Accessor();
        $privates_accessor->property_closure($container, 'tags', static function (array $tags) use ($type_to_forget): array {
            foreach ($tags as $tag_name => $tagged_classes) {
                foreach ($tagged_classes as $key => $tagged_class) {
                    if (is_a($tagged_class, $type_to_forget, \true)) {
                        unset($tags[$tag_name][$key]);
                    }
                }
            }
            return $tags;
        });
    }
}