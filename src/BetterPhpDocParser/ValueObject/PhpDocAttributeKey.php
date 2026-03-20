<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object;

use Rector\Php_Doc_Parser\Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key as NativePhpDocAttributeKey;
final class Php_Doc_Attribute_Key
{
    /**
     * @var string
     */
    public const START_AND_END = 'start_and_end';
    /**
     * Fully qualified name of identifier type class
     * @var string
     */
    public const RESOLVED_CLASS = 'resolved_class';
    /**
     * @var string
     */
    public const PARENT = Native_Php_Doc_Attribute_Key::PARENT;
    /**
     * @var string
     */
    public const LAST_PHP_DOC_TOKEN_POSITION = 'last_token_position';
    /**
     * @var string
     */
    public const ORIG_NODE = Native_Php_Doc_Attribute_Key::ORIG_NODE;
}