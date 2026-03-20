<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node\Identifier;
use Php_Stan\Php_Doc_Parser\Ast\Type\Generic_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Identifier_Type_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Class_String_Type;
use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Constant\Constant_Integer_Type;
use Php_Stan\Type\Generic\Generic_Class_String_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Never_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Union_Type_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Spacing_Aware_Array_Type_Node;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Php_Stan_Static_Type_Mapper;
use Rector\Type_Declaration\Node_Type_Analyzer\Detailed_Type_Analyzer;
use Rector\Type_Declaration\Type_Analyzer\Generic_Class_String_Type_Normalizer;
/**
 * @see \Rector\Tests\PHPStanStaticTypeMapper\TypeMapper\ArrayTypeMapperTest
 *
 * @implements TypeMapperInterface<ArrayType>
 */
final class Array_Type_Mapper implements Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private Generic_Class_String_Type_Normalizer $generic_class_string_type_normalizer;
    /**
     * @readonly
     */
    private Detailed_Type_Analyzer $detailed_type_analyzer;
    /**
     * @var string
     */
    public const HAS_GENERIC_TYPE_PARENT = 'has_generic_type_parent';
    private Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper;
    public function __construct(Generic_Class_String_Type_Normalizer $generic_class_string_type_normalizer, Detailed_Type_Analyzer $detailed_type_analyzer)
    {
        $this->generic_class_string_type_normalizer = $generic_class_string_type_normalizer;
        $this->detailed_type_analyzer = $detailed_type_analyzer;
    }
    // To avoid circular dependency
    public function autowire(Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper): void
    {
        $this->php_stan_static_type_mapper = $php_stan_static_type_mapper;
    }
    public function get_node_class(): string
    {
        return Array_Type::class;
    }
    /**
     * @param ArrayType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        // this cannot be handled by PHPStan $type->toPhpDocNode() as requires space removal around "|" in union type
        // then e.g. "int" instead of explicit number, and nice arrays
        $item_type = $type->get_iterable_value_type();
        $is_generic_array = $this->is_generic_array_candidate($type);
        if ($item_type instanceof Union_Type && !$type instanceof Constant_Array_Type && !$is_generic_array) {
            return $this->create_array_type_node_from_union_type($item_type);
        }
        if ($item_type instanceof Array_Type && $this->is_generic_array_candidate($item_type)) {
            return $this->create_generic_array_type($type, \true);
        }
        if ($is_generic_array) {
            return $this->create_generic_array_type($type, \true);
        }
        // keep "int" key in arary<int, mixed>
        if ($type->get_key_type() instanceof Integer_Type) {
            $key_type_node = $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($type->get_key_type());
            if (!$type->is_list()->maybe()) {
                $nested_type_node = $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($type->get_item_type());
                return new Generic_Type_Node(new Identifier_Type_Node('array'), [$key_type_node, $nested_type_node]);
            }
        }
        $item_type_node = $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($item_type);
        return new Spacing_Aware_Array_Type_Node($item_type_node);
    }
    /**
     * @param ArrayType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): Identifier
    {
        return new Identifier('array');
    }
    private function create_array_type_node_from_union_type(Union_Type $union_type): Spacing_Aware_Array_Type_Node
    {
        $unioned_array_type = [];
        foreach ($union_type->get_types() as $unioned_type) {
            $type_node = $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($unioned_type);
            $unioned_array_type[(string) $type_node] = $type_node;
        }
        if (count($unioned_array_type) > 1) {
            return new Spacing_Aware_Array_Type_Node(new Brackets_Aware_Union_Type_Node($unioned_array_type));
        }
        /** @var TypeNode $arrayType */
        $array_type = array_shift($unioned_array_type);
        return new Spacing_Aware_Array_Type_Node($array_type);
    }
    private function is_generic_array_candidate(Array_Type $array_type): bool
    {
        if ($array_type->get_key_type() instanceof Mixed_Type) {
            return \false;
        }
        if ($this->is_class_string_array_type($array_type)) {
            return \true;
        }
        // skip simple arrays, like "string[]", from converting to obvious "array<int, string>"
        if ($this->is_integer_key_and_non_nested_array($array_type)) {
            return \false;
        }
        if ($array_type->get_key_type() instanceof Never_Type) {
            return \false;
        }
        // make sure the integer key type is not natural/implicit array int keys
        $keys_array_type = $array_type->get_keys_array();
        if (!$keys_array_type instanceof Constant_Array_Type) {
            return \true;
        }
        foreach ($keys_array_type->get_value_types() as $key => $key_type) {
            if (!$key_type instanceof Constant_Integer_Type) {
                return \true;
            }
            if ($key !== $key_type->get_value()) {
                return \true;
            }
        }
        return \false;
    }
    private function create_generic_array_type(Array_Type $array_type, bool $with_key = \false): Generic_Type_Node
    {
        $item_type = $array_type->get_iterable_value_type();
        $item_type_node = $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($item_type);
        $identifier_type_node = new Identifier_Type_Node('array');
        // is class-string[] list only
        if ($this->is_class_string_array_type($array_type)) {
            $with_key = \false;
        }
        if ($with_key) {
            $key_type_node = $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($array_type->get_key_type());
            if ($item_type_node instanceof Brackets_Aware_Union_Type_Node && $this->is_pair_class_too_detailed($item_type)) {
                $generic_types = [$key_type_node, $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node(new Class_String_Type())];
            } else {
                $generic_types = [$key_type_node, $item_type_node];
            }
        } else {
            $generic_types = [$item_type_node];
        }
        // @see https://github.com/phpstan/phpdoc-parser/blob/98a088b17966bdf6ee25c8a4b634df313d8aa531/tests/PHPStan/Parser/PhpDocParserTest.php#L2692-L2696
        foreach ($generic_types as $generic_type) {
            /** @var TypeNode $genericType */
            $generic_type->set_attribute(self::HAS_GENERIC_TYPE_PARENT, $with_key);
        }
        $identifier_type_node->set_attribute(self::HAS_GENERIC_TYPE_PARENT, $with_key);
        return new Generic_Type_Node($identifier_type_node, $generic_types);
    }
    private function is_pair_class_too_detailed(Type $item_type): bool
    {
        if (!$item_type instanceof Union_Type) {
            return \false;
        }
        if (!$this->generic_class_string_type_normalizer->is_all_generic_class_string_type($item_type)) {
            return \false;
        }
        return $this->detailed_type_analyzer->is_too_detailed($item_type);
    }
    private function is_integer_key_and_non_nested_array(Array_Type $array_type): bool
    {
        if (!$array_type->get_key_type()->is_integer()->yes()) {
            return \false;
        }
        return !$array_type->get_iterable_value_type()->is_array()->yes();
    }
    private function is_class_string_array_type(Array_Type $array_type): bool
    {
        if ($array_type->get_key_type() instanceof Mixed_Type) {
            return $array_type->get_iterable_value_type() instanceof Generic_Class_String_Type;
        }
        if ($array_type->get_key_type() instanceof Constant_Integer_Type) {
            return $array_type->get_iterable_value_type() instanceof Generic_Class_String_Type;
        }
        return \false;
    }
}