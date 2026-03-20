<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Assign_Op;
use Php_Parser\Node\Expr\Assign_Op\Bitwise_And as AssignBitwiseAnd;
use Php_Parser\Node\Expr\Assign_Op\Bitwise_Or as AssignBitwiseOr;
use Php_Parser\Node\Expr\Assign_Op\Bitwise_Xor as AssignBitwiseXor;
use Php_Parser\Node\Expr\Assign_Op\Concat as AssignConcat;
use Php_Parser\Node\Expr\Assign_Op\Div as AssignDiv;
use Php_Parser\Node\Expr\Assign_Op\Minus as AssignMinus;
use Php_Parser\Node\Expr\Assign_Op\Mod as AssignMod;
use Php_Parser\Node\Expr\Assign_Op\Mul as AssignMul;
use Php_Parser\Node\Expr\Assign_Op\Plus as AssignPlus;
use Php_Parser\Node\Expr\Assign_Op\Pow as AssignPow;
use Php_Parser\Node\Expr\Assign_Op\Shift_Left as AssignShiftLeft;
use Php_Parser\Node\Expr\Assign_Op\Shift_Right as AssignShiftRight;
use Php_Parser\Node\Expr\Binary_Op;
use Php_Parser\Node\Expr\Binary_Op\Bitwise_And;
use Php_Parser\Node\Expr\Binary_Op\Bitwise_Or;
use Php_Parser\Node\Expr\Binary_Op\Bitwise_Xor;
use Php_Parser\Node\Expr\Binary_Op\Concat;
use Php_Parser\Node\Expr\Binary_Op\Div;
use Php_Parser\Node\Expr\Binary_Op\Equal;
use Php_Parser\Node\Expr\Binary_Op\Greater;
use Php_Parser\Node\Expr\Binary_Op\Greater_Or_Equal;
use Php_Parser\Node\Expr\Binary_Op\Identical;
use Php_Parser\Node\Expr\Binary_Op\Minus;
use Php_Parser\Node\Expr\Binary_Op\Mod;
use Php_Parser\Node\Expr\Binary_Op\Mul;
use Php_Parser\Node\Expr\Binary_Op\Not_Equal;
use Php_Parser\Node\Expr\Binary_Op\Not_Identical;
use Php_Parser\Node\Expr\Binary_Op\Plus;
use Php_Parser\Node\Expr\Binary_Op\Pow;
use Php_Parser\Node\Expr\Binary_Op\Shift_Left;
use Php_Parser\Node\Expr\Binary_Op\Shift_Right;
use Php_Parser\Node\Expr\Binary_Op\Smaller;
use Php_Parser\Node\Expr\Binary_Op\Smaller_Or_Equal;
use Php_Parser\Node\Expr\Boolean_Not;
use Php_Parser\Node\Expr\Cast\Bool_;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
final class Assign_And_Binary_Map
{
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    /**
     * @var array<class-string<BinaryOp>, class-string<BinaryOp>>
     */
    private const BINARY_OP_TO_INVERSE_CLASSES = [Identical::class => Not_Identical::class, Not_Identical::class => Identical::class, Equal::class => Not_Equal::class, Not_Equal::class => Equal::class, Greater::class => Smaller_Or_Equal::class, Smaller::class => Greater_Or_Equal::class, Greater_Or_Equal::class => Smaller::class, Smaller_Or_Equal::class => Greater::class];
    /**
     * @var array<class-string<AssignOp>, class-string<BinaryOp>>
     */
    private const ASSIGN_OP_TO_BINARY_OP_CLASSES = [Assign_Bitwise_Or::class => Bitwise_Or::class, Assign_Bitwise_And::class => Bitwise_And::class, Assign_Bitwise_Xor::class => Bitwise_Xor::class, Assign_Plus::class => Plus::class, Assign_Div::class => Div::class, Assign_Mul::class => Mul::class, Assign_Minus::class => Minus::class, Assign_Concat::class => Concat::class, Assign_Pow::class => Pow::class, Assign_Mod::class => Mod::class, Assign_Shift_Left::class => Shift_Left::class, Assign_Shift_Right::class => Shift_Right::class];
    /**
     * @var array<class-string<BinaryOp>, class-string<AssignOp>>
     */
    private array $binary_op_to_assign_classes = [];
    public function __construct(Node_Type_Resolver $node_type_resolver)
    {
        $this->node_type_resolver = $node_type_resolver;
        /** @var array<class-string<BinaryOp>, class-string<AssignOp>> $binaryClassesToAssignOp */
        $binary_classes_to_assign_op = array_flip(self::ASSIGN_OP_TO_BINARY_OP_CLASSES);
        $this->binary_op_to_assign_classes = $binary_classes_to_assign_op;
    }
    /**
     * @return class-string<BinaryOp|AssignOp>|null
     */
    public function get_alternative(Node $node): ?string
    {
        $node_class = get_class($node);
        if ($node instanceof Assign_Op) {
            return self::ASSIGN_OP_TO_BINARY_OP_CLASSES[$node_class] ?? null;
        }
        if ($node instanceof Binary_Op) {
            return $this->binary_op_to_assign_classes[$node_class] ?? null;
        }
        return null;
    }
    /**
     * @return class-string<BinaryOp>|null
     */
    public function get_inversed(Binary_Op $binary_op): ?string
    {
        $node_class = get_class($binary_op);
        return self::BINARY_OP_TO_INVERSE_CLASSES[$node_class] ?? null;
    }
    public function get_truthy_expr(Expr $expr): Expr
    {
        if ($expr instanceof Bool_) {
            return $expr;
        }
        if ($expr instanceof Boolean_Not) {
            return $expr;
        }
        $expr_type = $this->node_type_resolver->get_type($expr);
        // $type = $scope->getType($expr);
        if ($expr_type->is_boolean()->yes()) {
            return $expr;
        }
        return new Bool_($expr);
    }
}