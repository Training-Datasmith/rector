<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper\Type_Mapper;

use Php_Parser\Node;
use Php_Parser\Node\Complex_Type;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Intersection_Type as PHPParserNodeIntersectionType;
use Php_Parser\Node\Name;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Nullable_Type;
use Php_Parser\Node\Union_Type as PhpParserUnionType;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Never_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Brackets_Aware_Union_Type_Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Type\Spacing_Aware_Array_Type_Node;
use Rector\Node_Analyzer\Property_Analyzer;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Php_Stan_Static_Type_Mapper\Php_Stan_Static_Type_Mapper;
use Rector\Value_Object\Php_Version_Feature;
use Rector_Prefix202603\Webmozart\Assert\Assert;
use Rector_Prefix202603\Webmozart\Assert\InvalidArgumentException;
/**
 * @implements TypeMapperInterface<UnionType>
 */
final class Union_Type_Mapper implements Type_Mapper_Interface
{
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @readonly
     */
    private Property_Analyzer $property_analyzer;
    private Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper;
    public function __construct(Php_Version_Provider $php_version_provider, Property_Analyzer $property_analyzer)
    {
        $this->php_version_provider = $php_version_provider;
        $this->property_analyzer = $property_analyzer;
    }
    public function autowire(Php_Stan_Static_Type_Mapper $php_stan_static_type_mapper): void
    {
        $this->php_stan_static_type_mapper = $php_stan_static_type_mapper;
    }
    public function get_node_class(): string
    {
        return Union_Type::class;
    }
    /**
     * @param UnionType $type
     */
    public function map_to_php_stan_php_doc_type_node(Type $type): Brackets_Aware_Union_Type_Node
    {
        $union_types_nodes = [];
        $existing_types = [];
        foreach ($type->get_types() as $unioned_type) {
            if ($unioned_type instanceof Array_Type && $unioned_type->get_item_type() instanceof Never_Type) {
                $unioned_type = new Array_Type($unioned_type->get_key_type(), new Mixed_Type());
            }
            $unioned_type = $this->php_stan_static_type_mapper->map_to_php_stan_php_doc_type_node($unioned_type);
            if ($unioned_type instanceof Spacing_Aware_Array_Type_Node && $unioned_type->type instanceof Brackets_Aware_Union_Type_Node) {
                foreach ($unioned_type->type->types as $key => $inner_type_node) {
                    $printed_inner_type = (string) $inner_type_node;
                    if (in_array($printed_inner_type, $existing_types, \true)) {
                        unset($unioned_type->type->types[$key]);
                        continue;
                    }
                    $existing_types[] = $printed_inner_type;
                }
                if ($unioned_type->type->types === []) {
                    continue;
                }
            }
            $union_types_nodes[] = $unioned_type;
        }
        return new Brackets_Aware_Union_Type_Node($union_types_nodes);
    }
    /**
     * @param UnionType $type
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?Node
    {
        $php_parser_union_type = $this->match_php_parser_union_type($type, $type_kind);
        if ($php_parser_union_type instanceof Php_Parser_Union_Type) {
            return $this->resolve_union_type_node($php_parser_union_type);
        }
        return $php_parser_union_type;
    }
    /**
     * If type is nullable, and has only one other value,
     * this creates at least "?Type" in case of PHP 7.1-7.4
     * @return PhpParserUnionType|\PhpParser\Node\NullableType|null
     */
    private function resolve_type_with_nullable_php_parser_union_type(Php_Parser_Union_Type $php_parser_union_type)
    {
        $total_types = count($php_parser_union_type->types);
        if ($total_types === 2) {
            $php_parser_union_type->types = array_values($php_parser_union_type->types);
            $first_type = $php_parser_union_type->types[0];
            $second_type = $php_parser_union_type->types[1];
            try {
                Assert::is_any_of($first_type, [Name::class, Identifier::class]);
                Assert::is_any_of($second_type, [Name::class, Identifier::class]);
            } catch (InvalidArgumentException $exception) {
                return $this->resolve_union_types($php_parser_union_type);
            }
            $first_type_value = $first_type->to_string();
            $second_type_value = $second_type->to_string();
            if ($first_type_value === $second_type_value) {
                return $this->resolve_union_types($php_parser_union_type);
            }
            if ($first_type_value === 'null') {
                return $this->resolve_nullable_type(new Nullable_Type($second_type));
            }
            if ($second_type_value === 'null') {
                return $this->resolve_nullable_type(new Nullable_Type($first_type));
            }
        }
        return $this->resolve_union_types($php_parser_union_type);
    }
    /**
     * @return null|\PhpParser\Node\NullableType|PhpParserUnionType
     */
    private function resolve_nullable_type(Nullable_Type $nullable_type)
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::NULLABLE_TYPE)) {
            return null;
        }
        /** @var PHPParserNodeIntersectionType|Identifier|Name $type */
        $type = $nullable_type->type;
        if (!$type instanceof Php_Parser_Node_Intersection_Type) {
            // ?false is allowed only since PHP 8.2+, lets fallback to bool instead
            if ($type->to_string() === 'false' && !$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::NULL_FALSE_TRUE_STANDALONE_TYPE)) {
                return new Nullable_Type(new Identifier('bool'));
            }
            return $nullable_type;
        }
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::UNION_TYPES)) {
            return null;
        }
        $types = [$type];
        $types[] = new Identifier('null');
        return new Php_Parser_Union_Type($types);
    }
    private function resolve_union_types(Php_Parser_Union_Type $php_parser_union_type): ?Php_Parser_Union_Type
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::UNION_TYPES)) {
            return null;
        }
        return $php_parser_union_type;
    }
    private function has_object_and_static_type(Php_Parser_Union_Type $php_parser_union_type): bool
    {
        $has_anonymous_object_type = \false;
        $has_object_type = \false;
        foreach ($php_parser_union_type->types as $type) {
            if ($type instanceof Identifier && $type->to_string() === 'object') {
                $has_anonymous_object_type = \true;
                continue;
            }
            if ($type instanceof Fully_Qualified || $type instanceof Name && $type->is_special_class_name()) {
                $has_object_type = \true;
                continue;
            }
        }
        return $has_object_type && $has_anonymous_object_type;
    }
    /**
     * @return Name|FullyQualified|ComplexType|Identifier|null
     */
    private function match_php_parser_union_type(Union_Type $union_type, string $type_kind): ?Node
    {
        $php_parser_unioned_types = [];
        foreach ($union_type->get_types() as $unioned_type) {
            // NullType or ConstantBooleanType with false value inside UnionType is allowed
            // void type and mixed type are not allowed in union
            $php_parser_node = $this->php_stan_static_type_mapper->map_to_php_parser_node($unioned_type, Type_Kind::UNION);
            if ($php_parser_node === null) {
                return null;
            }
            // special callable type only not allowed on property
            if ($type_kind === Type_Kind::PROPERTY && $this->property_analyzer->is_forbidden_type($unioned_type)) {
                return null;
            }
            $php_parser_unioned_types[] = $php_parser_node;
        }
        /** @var Identifier[]|Name[] $phpParserUnionedTypes */
        $php_parser_unioned_types = array_unique($php_parser_unioned_types, \SORT_REGULAR);
        $count_php_parser_unioned_types = count($php_parser_unioned_types);
        if ($count_php_parser_unioned_types === 1) {
            return $php_parser_unioned_types[0];
        }
        return $this->resolve_type_with_nullable_php_parser_union_type(new Php_Parser_Union_Type($php_parser_unioned_types));
    }
    private function resolve_union_type_node(Php_Parser_Union_Type $php_parser_union_type): ?Php_Parser_Union_Type
    {
        if (!$this->php_version_provider->is_at_least_php_version(Php_Version_Feature::UNION_TYPES)) {
            return null;
        }
        // special case that would crash, when stdClass and object is used,
        if ($this->has_object_and_static_type($php_parser_union_type)) {
            return null;
        }
        return $php_parser_union_type;
    }
}