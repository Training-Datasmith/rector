<?php

declare (strict_types=1);
namespace Rector\Node_Decorator;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Property;
use Php_Stan\Type\Generic\Generic_Object_Type;
use Php_Stan\Type\Type;
use Rector\Better_Php_Doc_Parser\Php_Doc_Info\Php_Doc_Info_Factory;
use Rector\Better_Php_Doc_Parser\Php_Doc_Manipulator\Php_Doc_Type_Changer;
use Rector\Php\Php_Version_Provider;
use Rector\Php_Stan_Static_Type_Mapper\Enum\Type_Kind;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
use Rector\Value_Object\Php_Version_Feature;
final class Property_Type_Decorator
{
    /**
     * @readonly
     */
    private Php_Doc_Info_Factory $php_doc_info_factory;
    /**
     * @readonly
     */
    private Php_Version_Provider $php_version_provider;
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private Php_Doc_Type_Changer $php_doc_type_changer;
    public function __construct(Php_Doc_Info_Factory $php_doc_info_factory, Php_Version_Provider $php_version_provider, Static_Type_Mapper $static_type_mapper, Php_Doc_Type_Changer $php_doc_type_changer)
    {
        $this->php_doc_info_factory = $php_doc_info_factory;
        $this->php_version_provider = $php_version_provider;
        $this->static_type_mapper = $static_type_mapper;
        $this->php_doc_type_changer = $php_doc_type_changer;
    }
    public function decorate(Property $property, ?Type $type): void
    {
        if (!$type instanceof Type) {
            return;
        }
        $php_doc_info = $this->php_doc_info_factory->create_from_node_or_empty($property);
        if ($this->php_version_provider->is_at_least_php_version(Php_Version_Feature::TYPED_PROPERTIES)) {
            $php_parser_type = $this->static_type_mapper->map_php_stan_type_to_php_parser_node($type, Type_Kind::PROPERTY);
            if ($php_parser_type instanceof Node) {
                $property->type = $php_parser_type;
                if ($type instanceof Generic_Object_Type) {
                    $this->php_doc_type_changer->change_var_type($property, $php_doc_info, $type);
                }
                return;
            }
        }
        $this->php_doc_type_changer->change_var_type($property, $php_doc_info, $type);
    }
}