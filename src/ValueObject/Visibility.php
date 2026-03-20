<?php

declare (strict_types=1);
namespace Rector\Value_Object;

use Php_Parser\Modifiers;
final class Visibility
{
    /**
     * @var int
     */
    public const public = Modifiers::PUBLIC;
    /**
     * @var int
     */
    public const protected = Modifiers::PROTECTED;
    /**
     * @var int
     */
    public const private = Modifiers::PRIVATE;
    /**
     * @var int
     */
    public const static = Modifiers::STATIC;
    /**
     * @var int
     */
    public const abstract = Modifiers::ABSTRACT;
    /**
     * @var int
     */
    public const final = Modifiers::FINAL;
    /**
     * @var int
     */
    public const readonly = Modifiers::READONLY;
}