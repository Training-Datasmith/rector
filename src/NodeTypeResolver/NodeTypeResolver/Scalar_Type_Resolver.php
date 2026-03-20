<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Interpolated_String_Part;
use Php_Parser\Node\Scalar;
use Php_Parser\Node\Scalar\Float_;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\Interpolated_String;
use Php_Parser\Node\Scalar\Magic_Const;
use Php_Parser\Node\Scalar\String_;
use Php_Stan\Type\Constant\Constant_Float_Type;
use Php_Stan\Type\Constant\Constant_Integer_Type;
use Php_Stan\Type\Constant\Constant_String_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Rector\Exception\Not_Implemented_Yet_Exception;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
/**
 * @implements NodeTypeResolverInterface<Scalar>
 */
final class Scalar_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Scalar::class];
    }
    public function resolve(Node $node): Type
    {
        if ($node instanceof Float_) {
            return new Constant_Float_Type($node->value);
        }
        if ($node instanceof String_) {
            return new Constant_String_Type($node->value);
        }
        if ($node instanceof Int_) {
            return new Constant_Integer_Type($node->value);
        }
        if ($node instanceof Magic_Const) {
            return new Constant_String_Type($node->get_name());
        }
        if ($node instanceof Interpolated_String) {
            return new String_Type();
        }
        if ($node instanceof Interpolated_String_Part) {
            return new Constant_String_Type($node->value);
        }
        throw new Not_Implemented_Yet_Exception();
    }
}