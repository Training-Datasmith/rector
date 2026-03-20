<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
final class Callable_Php_Doc_Node_Visitor extends \Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor
{
    /**
     * @readonly
     */
    private ?string $doc_content;
    /**
     * @var callable(Node, string|null): (int|null|Node)
     */
    private $callable;
    /**
     * @param callable(Node $callable, string|null $docContent): (int|null|Node) $callable
     */
    public function __construct(callable $callable, ?string $doc_content)
    {
        $this->doc_content = $doc_content;
        $this->callable = $callable;
    }
    /**
     * @return int|\PHPStan\PhpDocParser\Ast\Node|null
     */
    public function enter_node(Node $node)
    {
        $callable = $this->callable;
        return $callable($node, $this->doc_content);
    }
}