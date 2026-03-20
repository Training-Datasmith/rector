<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Doctrine_Annotation;

final class Silent_Key_Map
{
    /**
     * @var array<string, string>
     */
    public const CLASS_NAMES_TO_SILENT_KEYS = ['Symfony\Component\Routing\Annotation\Route' => 'path'];
}