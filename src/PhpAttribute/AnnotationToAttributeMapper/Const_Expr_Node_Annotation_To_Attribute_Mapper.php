<?php

declare (strict_types=1);
namespace Rector\Php_Attribute\Annotation_To_Attribute_Mapper;

use Php_Parser\Builder_Helpers;
use Php_Parser\Node\Expr;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_False_Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_Integer_Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_Node;
use Php_Stan\Php_Doc_Parser\Ast\Const_Expr\Const_Expr_True_Node;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Constant\Constant_Float_Type;
use Rector\Exception\Not_Implemented_Yet_Exception;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
/**
 * @implements AnnotationToAttributeMapperInterface<ConstExprNode>
 */
final class Const_Expr_Node_Annotation_To_Attribute_Mapper implements Annotation_To_Attribute_Mapper_Interface
{
    /**
     * @param mixed $value
     */
    public function is_candidate($value): bool
    {
        return $value instanceof Const_Expr_Node;
    }
    /**
     * @param ConstExprNode $value
     */
    public function map($value): Expr
    {
        if ($value instanceof Const_Expr_Integer_Node) {
            return Builder_Helpers::normalize_value((int) $value->value);
        }
        if ($value instanceof Constant_Float_Type || $value instanceof Constant_Boolean_Type) {
            return Builder_Helpers::normalize_value($value->get_value());
        }
        if ($value instanceof Const_Expr_True_Node) {
            return Builder_Helpers::normalize_value(\true);
        }
        if ($value instanceof Const_Expr_False_Node) {
            return Builder_Helpers::normalize_value(\false);
        }
        throw new Not_Implemented_Yet_Exception();
    }
}