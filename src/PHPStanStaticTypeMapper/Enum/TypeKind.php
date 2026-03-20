<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Enum;

final class Type_Kind
{
    /**
     * @var string
     */
    public const PROPERTY = 'property';
    /**
     * @var string
     */
    public const RETURN = 'return';
    /**
     * @var string
     */
    public const PARAM = 'param';
    /**
     * @var string
     */
    public const UNION = 'union';
}