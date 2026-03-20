<?php

declare (strict_types=1);
namespace Rector\Custom_Rules;

use Php_Parser\Modifiers;
use Php_Parser\Node;
use Php_Parser\Node\Expr\Include_;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Stmt\Group_Use;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
/**
 * Inspired by @see \PhpParser\NodeDumper
 */
final class Simple_Node_Dumper
{
    /**
     * @var array<int, string>
     */
    private const INCLUDE_TYPE_MAP = [Include_::TYPE_INCLUDE => 'TYPE_INCLUDE', Include_::TYPE_INCLUDE_ONCE => 'TYPE_INCLUDE_ONCE', Include_::TYPE_REQUIRE => 'TYPE_REQUIRE', Include_::TYPE_REQUIRE_ONCE => 'TYPE_REQUIRE_ONCE'];
    /**
     * @param Node[]|Node|mixed[] $node
     */
    public static function dump($node, bool $root_node = \true): string
    {
        // display single root node directly to avoid useless nesting in output
        if (is_array($node) && count($node) === 1 && $root_node) {
            $node = $node[0];
        }
        if ($node instanceof Node) {
            return self::dump_single_node($node);
        }
        if (self::is_string_list($node)) {
            return json_encode($node, \JSON_THROW_ON_ERROR);
        }
        $result = '[';
        foreach ($node as $key => $value) {
            $result .= "\n    " . $key . ': ';
            if ($value === null) {
                $result .= 'null';
            } elseif ($value === \false) {
                $result .= 'false';
            } elseif ($value === \true) {
                $result .= 'true';
            } elseif (is_string($value)) {
                $result .= '"' . $value . '"';
            } elseif (is_scalar($value)) {
                $result .= $value;
            } else {
                $result .= str_replace("\n", "\n    ", self::dump($value, \false));
            }
        }
        if (count($node) === 0) {
            $result .= ']';
        } else {
            $result .= "\n]";
        }
        return $result;
    }
    /**
     * @param mixed[] $items
     */
    private static function is_string_list(array $items): bool
    {
        foreach ($items as $item) {
            if (!is_string($item)) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @param mixed $flags
     */
    private static function dump_flags(string $flags): string
    {
        $strs = [];
        if (($flags & Modifiers::PUBLIC) !== 0) {
            $strs[] = 'MODIFIER_PUBLIC';
        }
        if (($flags & Modifiers::PROTECTED) !== 0) {
            $strs[] = 'MODIFIER_PROTECTED';
        }
        if (($flags & Modifiers::PRIVATE) !== 0) {
            $strs[] = 'MODIFIER_PRIVATE';
        }
        if (($flags & Modifiers::ABSTRACT) !== 0) {
            $strs[] = 'MODIFIER_ABSTRACT';
        }
        if (($flags & Modifiers::STATIC) !== 0) {
            $strs[] = 'MODIFIER_STATIC';
        }
        if (($flags & Modifiers::FINAL) !== 0) {
            $strs[] = 'MODIFIER_FINAL';
        }
        if (($flags & Modifiers::READONLY) !== 0) {
            $strs[] = 'MODIFIER_READONLY';
        }
        if ($strs !== []) {
            return implode(' | ', $strs) . ' (' . $flags . ')';
        }
        return $flags;
    }
    /**
     * @param int|float|string $type
     */
    private static function dump_include_type($type): string
    {
        if (!isset(self::INCLUDE_TYPE_MAP[$type])) {
            return (string) $type;
        }
        return self::INCLUDE_TYPE_MAP[$type] . ' (' . $type . ')';
    }
    /**
     * @param mixed $type
     */
    private static function dump_use_type($type): string
    {
        $map = [Use_::TYPE_UNKNOWN => 'TYPE_UNKNOWN', Use_::TYPE_NORMAL => 'TYPE_NORMAL', Use_::TYPE_FUNCTION => 'TYPE_FUNCTION', Use_::TYPE_CONSTANT => 'TYPE_CONSTANT'];
        if (!isset($map[$type])) {
            return (string) $type;
        }
        return $map[$type] . ' (' . $type . ')';
    }
    private static function dump_single_node(Node $node): string
    {
        $result = get_class($node);
        // print simple nodes on same line, to make output more readable
        if ($node instanceof Variable && is_string($node->name)) {
            $result .= '( name: "' . $node->name . '" )';
        } elseif ($node instanceof Identifier) {
            $result .= '( name: "' . $node->name . '" )';
        } elseif ($node instanceof Name) {
            $result .= '( parts: ' . json_encode($node->get_parts(), \JSON_THROW_ON_ERROR) . ' )';
        } elseif ($node instanceof Scalar && $node->get_sub_node_names() === ['value']) {
            if (is_string($node->value)) {
                $result .= '( value: "' . $node->value . '" )';
            } else {
                $result .= '( value: ' . $node->value . ' )';
            }
        } else {
            $result .= '(';
            foreach ($node->get_sub_node_names() as $key) {
                $result .= "\n    " . $key . ': ';
                $value = $node->{$key};
                if ($value === null) {
                    $result .= 'null';
                } elseif ($value === \false) {
                    $result .= 'false';
                } elseif ($value === \true) {
                    $result .= 'true';
                } elseif (is_scalar($value)) {
                    if ($key === 'flags' || $key === 'newModifier') {
                        $result .= self::dump_flags($value);
                    } elseif ($key === 'type' && $node instanceof Include_) {
                        $result .= self::dump_include_type($value);
                    } elseif ($key === 'type' && ($node instanceof Use_ || $node instanceof Use_Item || $node instanceof Group_Use)) {
                        $result .= self::dump_use_type($value);
                    } elseif (is_string($value)) {
                        $result .= '"' . $value . '"';
                    } else {
                        $result .= $value;
                    }
                } else {
                    $result .= str_replace("\n", "\n    ", self::dump($value, \false));
                }
            }
            $result .= "\n)";
        }
        return $result;
    }
}