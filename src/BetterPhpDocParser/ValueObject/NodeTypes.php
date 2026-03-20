<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Value_Object;

use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Param_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Php_Doc_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Property_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Return_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Template_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Throws_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Var_Tag_Value_Node;
use Rector\Enum\Class_Name;
final class Node_Types
{
    /**
     * @var array<class-string<PhpDocTagValueNode>>
     */
    public const TYPE_AWARE_NODES = [Var_Tag_Value_Node::class, Param_Tag_Value_Node::class, Return_Tag_Value_Node::class, Throws_Tag_Value_Node::class, Property_Tag_Value_Node::class, Template_Tag_Value_Node::class];
    /**
     * @var string[]
     */
    public const TYPE_AWARE_DOCTRINE_ANNOTATION_CLASSES = [Class_Name::JMS_TYPE, 'Doctrine\ORM\Mapping\OneToMany', 'Symfony\Component\Validator\Constraints\Choice', 'Symfony\Component\Validator\Constraints\Email', 'Symfony\Component\Validator\Constraints\Range'];
}