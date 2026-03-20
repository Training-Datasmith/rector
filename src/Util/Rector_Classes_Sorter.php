<?php

declare (strict_types=1);
namespace Rector\Util;

use Rector\Contract\Rector\Rector_Interface;
use Rector\Post_Rector\Contract\Rector\Post_Rector_Interface;
final class Rector_Classes_Sorter
{
    /**
     * @param array<class-string<RectorInterface|PostRectorInterface>> $rectorClasses
     * @return array<class-string<RectorInterface>>
     */
    public static function sort_and_filter_out_post_rectors(array $rector_classes): array
    {
        $rector_classes = array_unique($rector_classes);
        $main_rector_classes = array_filter($rector_classes, fn(string $rector_class): bool => is_a($rector_class, Rector_Interface::class, \true));
        sort($main_rector_classes);
        return $main_rector_classes;
    }
}