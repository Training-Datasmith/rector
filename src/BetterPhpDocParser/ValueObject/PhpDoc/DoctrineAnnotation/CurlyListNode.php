<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation;

use Rector\Better_Php_Doc_Parser\Php_Doc\Array_Item_Node;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Curly_List_Node extends \Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc\Doctrine_Annotation\Abstract_Values_Aware_Node
{
    /**
     * @var ArrayItemNode[]
     * @readonly
     */
    private array $array_item_nodes = [];
    /**
     * @param ArrayItemNode[] $arrayItemNodes
     */
    public function __construct(array $array_item_nodes = [])
    {
        $this->array_item_nodes = $array_item_nodes;
        Assert::all_is_instance_of($this->array_item_nodes, Array_Item_Node::class);
        parent::__construct($this->array_item_nodes);
    }
    public function __toString(): string
    {
        // possibly list items
        return $this->implode($this->values);
    }
    /**
     * @param ArrayItemNode[] $array
     */
    private function implode(array $array): string
    {
        $item_contents = '';
        $last_item_key = array_key_last($array);
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $item_contents .= (string) $value;
            } else {
                $item_contents .= $key . '=' . $value;
            }
            if ($last_item_key !== $key) {
                $item_contents .= ', ';
            }
        }
        return '{' . $item_contents . '}';
    }
}