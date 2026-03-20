<?php

declare (strict_types=1);
namespace Rector\Php_Stan_Static_Type_Mapper;

use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Type;
use Rector\Exception\Not_Implemented_Yet_Exception;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Php_Stan_Static_Type_Mapper
{
    /**
     * @var TypeMapperInterface[]
     * @readonly
     */
    private array $type_mappers;
    /**
     * @param TypeMapperInterface[] $typeMappers
     */
    public function __construct(array $type_mappers)
    {
        $this->type_mappers = $type_mappers;
        Assert::not_empty($type_mappers);
    }
    public function map_to_php_stan_php_doc_type_node(Type $type): Type_Node
    {
        foreach ($this->type_mappers as $type_mapper) {
            if (!is_a($type, $type_mapper->get_node_class(), \true)) {
                continue;
            }
            return $type_mapper->map_to_php_stan_php_doc_type_node($type);
        }
        throw new Not_Implemented_Yet_Exception(__METHOD__ . ' for ' . get_class($type));
    }
    /**
     * @param TypeKind::* $typeKind
     * @return \PhpParser\Node\Name|\PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|null
     */
    public function map_to_php_parser_node(Type $type, string $type_kind): ?\Php_Parser\Node
    {
        foreach ($this->type_mappers as $type_mapper) {
            if (!is_a($type, $type_mapper->get_node_class(), \true)) {
                continue;
            }
            return $type_mapper->map_to_php_parser_node($type, $type_kind);
        }
        throw new Not_Implemented_Yet_Exception(__METHOD__ . ' for ' . get_class($type));
    }
}