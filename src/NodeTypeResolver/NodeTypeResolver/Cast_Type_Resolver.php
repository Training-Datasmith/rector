<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Node_Type_Resolver;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Cast;
use Php_Parser\Node\Expr\Cast\Array_;
use Php_Parser\Node\Expr\Cast\Bool_;
use Php_Parser\Node\Expr\Cast\Double;
use Php_Parser\Node\Expr\Cast\Int_;
use Php_Parser\Node\Expr\Cast\Object_;
use Php_Parser\Node\Expr\Cast\String_;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Float_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Rector\Exception\Not_Implemented_Yet_Exception;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
/**
 * @implements NodeTypeResolverInterface<Cast>
 */
final class Cast_Type_Resolver implements Node_Type_Resolver_Interface
{
    /**
     * @var array<class-string<Node>, class-string<Type>>
     */
    private const CAST_CLASS_TO_TYPE_MAP = [Bool_::class => Boolean_Type::class, String_::class => String_Type::class, Int_::class => Integer_Type::class, Double::class => Float_Type::class];
    /**
     * @return array<class-string<Node>>
     */
    public function get_node_classes(): array
    {
        return [Cast::class];
    }
    /**
     * @param Cast $node
     */
    public function resolve(Node $node): Type
    {
        foreach (self::CAST_CLASS_TO_TYPE_MAP as $cast_class => $type_class) {
            if ($node instanceof $cast_class) {
                return new $type_class();
            }
        }
        if ($node instanceof Array_) {
            return new Array_Type(new Mixed_Type(), new Mixed_Type());
        }
        if ($node instanceof Object_) {
            return new Object_Type('stdClass');
        }
        throw new Not_Implemented_Yet_Exception(get_class($node));
    }
}