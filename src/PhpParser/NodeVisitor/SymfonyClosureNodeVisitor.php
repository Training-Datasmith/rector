<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Node_Traverser\Simple_Node_Traverser;
use Rector\Symfony\Node_Analyzer\Symfony_Php_Closure_Detector;
final class Symfony_Closure_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    /**
     * @readonly
     */
    private Symfony_Php_Closure_Detector $symfony_php_closure_detector;
    public function __construct(Symfony_Php_Closure_Detector $symfony_php_closure_detector)
    {
        $this->symfony_php_closure_detector = $symfony_php_closure_detector;
    }
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Closure) {
            return null;
        }
        if (!$this->symfony_php_closure_detector->detect($node)) {
            return null;
        }
        Simple_Node_Traverser::decorate_with_attribute_value($node->stmts, Attribute_Key::IS_INSIDE_SYMFONY_PHP_CLOSURE, \true);
        return null;
    }
}