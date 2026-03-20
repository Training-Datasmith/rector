<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Type;

use Override;
use Php_Stan\Php_Doc_Parser\Ast\Type\Callable_Type_Node;
final class Spacing_Aware_Callable_Type_Node extends Callable_Type_Node
{
    #[Override]
    public function __toString(): string
    {
        // keep original (Psalm?) format, see https://github.com/rectorphp/rector/issues/2841
        return $this->create_explicit_callable();
    }
    private function create_explicit_callable(): string
    {
        $parameter_type_string = $this->create_parameter_type_string();
        $return_type_as_string = (string) $this->return_type;
        if (strpos($return_type_as_string, '|') !== \false) {
            $return_type_as_string = '(' . $return_type_as_string . ')';
        }
        $parameter_type_string = $this->normalize_parameter_type($parameter_type_string, $return_type_as_string);
        $return_type_as_string = $this->normalize_return_type($parameter_type_string, $return_type_as_string);
        return sprintf('%s%s%s', $this->identifier->name, $parameter_type_string, $return_type_as_string);
    }
    private function create_parameter_type_string(): string
    {
        $parameter_type_strings = [];
        foreach ($this->parameters as $parameter) {
            $parameter_type_strings[] = trim((string) $parameter);
        }
        $parameter_type_string = implode(', ', $parameter_type_strings);
        return trim($parameter_type_string);
    }
    private function normalize_parameter_type(string $parameter_type_string, string $return_type_as_string): string
    {
        if ($parameter_type_string !== '') {
            return '(' . $parameter_type_string . ')';
        }
        if ($return_type_as_string === 'mixed') {
            return $parameter_type_string;
        }
        if ($return_type_as_string === '') {
            return $parameter_type_string;
        }
        return '()';
    }
    private function normalize_return_type(string $parameter_type_string, string $return_type_as_string): string
    {
        if ($return_type_as_string !== 'mixed') {
            return ':' . $return_type_as_string;
        }
        if ($parameter_type_string !== '') {
            return ':' . $return_type_as_string;
        }
        return '';
    }
}