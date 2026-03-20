<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Annotation;

final class Annotation_Naming
{
    public function normalize_name(string $name): string
    {
        return '@' . ltrim($name, '@');
    }
}