<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc;

use Override;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Template_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Type\Type_Node;
final class Spacing_Aware_Template_Tag_Value_Node extends Template_Tag_Value_Node
{
    /**
     * @readonly
     */
    private string $preposition;
    public function __construct(string $name, ?Type_Node $type_node, string $description, string $preposition)
    {
        $this->preposition = $preposition;
        parent::__construct($name, $type_node, $description);
    }
    #[Override]
    public function __toString(): string
    {
        // @see https://github.com/rectorphp/rector/issues/3438
        # 'as'/'of'
        $bound = $this->bound instanceof Type_Node ? ' ' . $this->preposition . ' ' . $this->bound : '';
        $content = $this->name . $bound . ' ' . $this->description;
        return trim($content);
    }
}