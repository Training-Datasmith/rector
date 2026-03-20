<?php

declare (strict_types=1);
namespace Rector\Static_Type_Mapper\Php_Doc;

use Php_Parser\Node;
use Php_Stan\Analyser\Name_Scope;
use Php_Stan\Php_Doc\Type_Node_Resolver;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
use Php_Stan\Type\Type;
use Rector\Static_Type_Mapper\Contract\Php_Doc_Parser\Php_Doc_Type_Mapper_Interface;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\StaticTypeMapper\PhpDoc\PhpDocTypeMapperTest
 */
final class Php_Doc_Type_Mapper
{
    /**
     * @var PhpDocTypeMapperInterface[]
     * @readonly
     */
    private array $php_doc_type_mappers;
    /**
     * @readonly
     */
    private Type_Node_Resolver $type_node_resolver;
    /**
     * @param PhpDocTypeMapperInterface[] $phpDocTypeMappers
     */
    public function __construct(array $php_doc_type_mappers, Type_Node_Resolver $type_node_resolver)
    {
        $this->php_doc_type_mappers = $php_doc_type_mappers;
        $this->type_node_resolver = $type_node_resolver;
        Assert::not_empty($php_doc_type_mappers);
    }
    public function map_to_php_stan_type(Type_Node $type_node, Node $node, Name_Scope $name_scope): Type
    {
        foreach ($this->php_doc_type_mappers as $php_doc_type_mapper) {
            if (!is_a($type_node, $php_doc_type_mapper->get_node_type())) {
                continue;
            }
            return $php_doc_type_mapper->map_to_php_stan_type($type_node, $node, $name_scope);
        }
        // fallback to PHPStan resolver
        return $this->type_node_resolver->resolve($type_node, $name_scope);
    }
}