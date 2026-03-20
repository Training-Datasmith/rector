<?php

declare (strict_types=1);
namespace Rector\Dependency_Injection;

use Php_Parser\Lexer;
use Php_Stan\Analyser\Node_Scope_Resolver;
use Php_Stan\Analyser\Scope_Factory;
use Php_Stan\Parser\Parser;
use Php_Stan\Php_Doc\Type_Node_Resolver;
use Php_Stan\Php_Doc_Parser\Parser_Config;
use Php_Stan\Reflection\Reflection_Provider;
use Rector\Application\Changed_Node_Scope_Refresher;
use Rector\Application\File_Processor;
use Rector\Application\Provider\Current_File_Provider;
use Rector\Better_Php_Doc_Parser\Comment\Comments_Merger;
use Rector\Better_Php_Doc_Parser\Contract\Base_Php_Doc_Node_Visitor_Interface;
use Rector\Better_Php_Doc_Parser\Contract\Php_Doc_Parser\Php_Doc_Node_Decorator_Interface;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Mapper;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor\Array_Type_Php_Doc_Node_Visitor;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor\Callable_Type_Php_Doc_Node_Visitor;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor\Intersection_Type_Node_Php_Doc_Node_Visitor;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor\Template_Php_Doc_Node_Visitor;
use Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor\Union_Type_Node_Php_Doc_Node_Visitor;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Array_Item_Class_Name_Decorator;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Better_Php_Doc_Parser;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Const_Expr_Class_Name_Decorator;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Doctrine_Annotation_Decorator;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Tag_Generic_Uses_Decorator;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Array_Parser;
use Rector\Better_Php_Doc_Parser\Php_Doc_Parser\Static_Doctrine_Annotation_Parser\Plain_Value_Parser;
use Rector\Caching\Cache;
use Rector\Caching\Cache_Factory;
use Rector\Changes_Reporting\Contract\Output\Output_Formatter_Interface;
use Rector\Changes_Reporting\Output\Console_Output_Formatter;
use Rector\Changes_Reporting\Output\Git_Hub_Output_Formatter;
use Rector\Changes_Reporting\Output\Gitlab_Output_Formatter;
use Rector\Changes_Reporting\Output\Json_Output_Formatter;
use Rector\Changes_Reporting\Output\J_Unit_Output_Formatter;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skipper;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skip_Voter\Alias_Class_Name_Import_Skip_Voter;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skip_Voter\Class_Like_Name_Class_Name_Import_Skip_Voter;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skip_Voter\Fully_Qualified_Name_Class_Name_Import_Skip_Voter;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skip_Voter\Original_Name_Import_Skip_Voter;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skip_Voter\Reserved_Class_Name_Import_Skip_Voter;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skip_Voter\Short_Class_Import_Skip_Voter;
use Rector\Coding_Style\Class_Name_Import\Class_Name_Import_Skip_Voter\Uses_Class_Name_Import_Skip_Voter;
use Rector\Coding_Style\Contract\Class_Name_Import\Class_Name_Import_Skip_Voter_Interface;
use Rector\Config\Rector_Config;
use Rector\Configuration\Config_Initializer;
use Rector\Configuration\Configuration_Rule_Filter;
use Rector\Configuration\Only_Rule_Resolver;
use Rector\Configuration\Renamed_Classes_Data_Collector;
use Rector\Console\Command\Custom_Rule_Command;
use Rector\Console\Command\List_Rules_Command;
use Rector\Console\Command\Process_Command;
use Rector\Console\Command\Setup_Ci_Command;
use Rector\Console\Command\Worker_Command;
use Rector\Console\Console_Application;
use Rector\Console\Output\Output_Formatter_Collector;
use Rector\Console\Style\Rector_Style;
use Rector\Console\Style\Symfony_Style_Factory;
use Rector\Contract\Dependency_Injection\Resettable_Interface;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Node_Decorator\Created_By_Rule_Decorator;
use Rector\Node_Name_Resolver\Contract\Node_Name_Resolver_Interface;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Class_Const_Fetch_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Class_Const_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Class_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Func_Call_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Function_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Name_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Param_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Property_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Use_Name_Resolver;
use Rector\Node_Name_Resolver\Node_Name_Resolver\Variable_Name_Resolver;
use Rector\Node_Type_Resolver\Contract\Node_Type_Resolver_Interface;
use Rector\Node_Type_Resolver\Dependency_Injection\Php_Stan_Services_Factory;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Cast_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Class_And_Interface_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Class_Const_Fetch_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Identifier_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Name_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\New_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Param_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Property_Fetch_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Property_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Scalar_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Static_Call_Method_Call_Type_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver\Trait_Type_Resolver;
use Rector\Node_Type_Resolver\Php_Stan\Scope\Php_Stan_Node_Scope_Resolver;
use Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator_Provider\Dynamic_Source_Locator_Provider;
use Rector\Php80\Attribute_Decorator\Doctrine_Converter_Attribute_Decorator;
use Rector\Php80\Attribute_Decorator\Sensio_Param_Converter_Attribute_Decorator;
use Rector\Php80\Contract\Converter_Attribute_Decorator_Interface;
use Rector\Php80\Node_Manipulator\Attribute_Group_Named_Argument_Manipulator;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\Array_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\Array_Item_Node_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\Class_Const_Fetch_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\Const_Expr_Node_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\Curly_List_Node_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\Doctrine_Annotation_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\String_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Annotation_To_Attribute_Mapper\String_Node_Annotation_To_Attribute_Mapper;
use Rector\Php_Attribute\Contract\Annotation_To_Attribute_Mapper_Interface;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Comparing\Node_Comparator;
use Rector\Php_Parser\Node\Node_Factory;
use Rector\Php_Parser\Node_Traverser\Rector_Node_Traverser;
use Rector\Php_Parser\Node_Visitor\Arg_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Assigned_To_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\By_Ref_Return_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\By_Ref_Variable_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Call_Like_This_Bound_Closure_Args_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Class_Const_Fetch_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Closure_With_Variadic_Parameters_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Context_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Global_Variable_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Name_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Param_Default_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Php_Version_Condition_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Property_Or_Class_Const_Default_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Static_Variable_Node_Visitor;
use Rector\Php_Parser\Node_Visitor\Symfony_Closure_Node_Visitor;
use Rector\Php_Stan_Static_Type_Mapper\Contract\Type_Mapper_Interface;
use Rector\Php_Stan_Static_Type_Mapper\Php_Stan_Static_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Accessory_Literal_String_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Accessory_Non_Empty_String_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Accessory_Non_Falsy_String_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Accessory_Numeric_String_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Array_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Boolean_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Callable_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Class_String_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Closure_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Conditional_Type_For_Parameter_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Conditional_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Constant_Array_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Float_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Generic_Class_String_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Has_Method_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Has_Offset_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Has_Offset_Value_Type_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Has_Property_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Integer_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Intersection_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Iterable_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Mixed_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Never_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Non_Empty_Array_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Null_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Object_Without_Class_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Oversized_Array_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Parent_Static_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Resource_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Self_Object_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Static_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Strict_Mixed_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\String_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\This_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Type_With_Class_Name_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Union_Type_Mapper;
use Rector\Php_Stan_Static_Type_Mapper\Type_Mapper\Void_Type_Mapper;
use Rector\Post_Rector\Application\Post_File_Processor;
use Rector\Rector\Abstract_Rector;
use Rector\Reporting\Deprecated_Rules_Reporter;
use Rector\Skipper\Skipper\Skipper;
use Rector\Static_Type_Mapper\Contract\Php_Doc_Parser\Php_Doc_Type_Mapper_Interface;
use Rector\Static_Type_Mapper\Contract\Php_Parser\Php_Parser_Node_Mapper_Interface;
use Rector\Static_Type_Mapper\Mapper\Php_Parser_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Doc\Php_Doc_Type_Mapper;
use Rector\Static_Type_Mapper\Php_Doc_Parser\Identifier_Php_Doc_Type_Mapper;
use Rector\Static_Type_Mapper\Php_Doc_Parser\Intersection_Php_Doc_Type_Mapper;
use Rector\Static_Type_Mapper\Php_Doc_Parser\Nullable_Php_Doc_Type_Mapper;
use Rector\Static_Type_Mapper\Php_Doc_Parser\Union_Php_Doc_Type_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\Expr_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\Fully_Qualified_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\Identifier_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\Intersection_Type_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\Name_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\Nullable_Type_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\String_Node_Mapper;
use Rector\Static_Type_Mapper\Php_Parser\Union_Type_Node_Mapper;
use Rector_Prefix202603\Doctrine\Inflector\Inflector;
use Rector_Prefix202603\Doctrine\Inflector\Rules\English\Inflector_Factory;
use Rector_Prefix202603\Illuminate\Container\Container;
use Rector_Prefix202603\Symfony\Component\Console\Application;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Lazy_Container_Factory
{
    /**
     * @var array<class-string<NodeNameResolverInterface>>
     */
    private const NODE_NAME_RESOLVER_CLASSES = [Class_Const_Fetch_Name_Resolver::class, Class_Const_Name_Resolver::class, Class_Name_Resolver::class, Func_Call_Name_Resolver::class, Function_Name_Resolver::class, Name_Name_Resolver::class, Param_Name_Resolver::class, Property_Name_Resolver::class, Use_Name_Resolver::class, Variable_Name_Resolver::class];
    /**
     * @var array<class-string<BasePhpDocNodeVisitorInterface>>
     */
    private const BASE_PHP_DOC_NODE_VISITORS = [Array_Type_Php_Doc_Node_Visitor::class, Callable_Type_Php_Doc_Node_Visitor::class, Intersection_Type_Node_Php_Doc_Node_Visitor::class, Template_Php_Doc_Node_Visitor::class, Union_Type_Node_Php_Doc_Node_Visitor::class];
    /**
     * @var array<class-string<AnnotationToAttributeMapperInterface>>
     */
    private const ANNOTATION_TO_ATTRIBUTE_MAPPER_CLASSES = [Array_Annotation_To_Attribute_Mapper::class, Array_Item_Node_Annotation_To_Attribute_Mapper::class, Class_Const_Fetch_Annotation_To_Attribute_Mapper::class, Const_Expr_Node_Annotation_To_Attribute_Mapper::class, Curly_List_Node_Annotation_To_Attribute_Mapper::class, Doctrine_Annotation_Annotation_To_Attribute_Mapper::class, String_Annotation_To_Attribute_Mapper::class, String_Node_Annotation_To_Attribute_Mapper::class];
    /**
     * @var array<class-string<DecoratingNodeVisitorInterface>>
     */
    private const DECORATING_NODE_VISITOR_CLASSES = [Arg_Node_Visitor::class, Closure_With_Variadic_Parameters_Node_Visitor::class, Php_Version_Condition_Node_Visitor::class, Assigned_To_Node_Visitor::class, Symfony_Closure_Node_Visitor::class, By_Ref_Return_Node_Visitor::class, By_Ref_Variable_Node_Visitor::class, Context_Node_Visitor::class, Global_Variable_Node_Visitor::class, Name_Node_Visitor::class, Static_Variable_Node_Visitor::class, Property_Or_Class_Const_Default_Node_Visitor::class, Param_Default_Node_Visitor::class, Class_Const_Fetch_Node_Visitor::class, Call_Like_This_Bound_Closure_Args_Node_Visitor::class];
    /**
     * @var array<class-string<PhpDocTypeMapperInterface>>
     */
    private const PHPDOC_TYPE_MAPPER_CLASSES = [Identifier_Php_Doc_Type_Mapper::class, Intersection_Php_Doc_Type_Mapper::class, Nullable_Php_Doc_Type_Mapper::class, Union_Php_Doc_Type_Mapper::class];
    /**
     * @var array<class-string<ClassNameImportSkipVoterInterface>>
     */
    private const CLASS_NAME_IMPORT_SKIPPER_CLASSES = [Alias_Class_Name_Import_Skip_Voter::class, Class_Like_Name_Class_Name_Import_Skip_Voter::class, Fully_Qualified_Name_Class_Name_Import_Skip_Voter::class, Uses_Class_Name_Import_Skip_Voter::class, Reserved_Class_Name_Import_Skip_Voter::class, Short_Class_Import_Skip_Voter::class, Original_Name_Import_Skip_Voter::class];
    /**
     * @var array<class-string<TypeMapperInterface>>
     */
    private const TYPE_MAPPER_CLASSES = [Accessory_Literal_String_Type_Mapper::class, Accessory_Non_Empty_String_Type_Mapper::class, Accessory_Non_Falsy_String_Type_Mapper::class, Accessory_Numeric_String_Type_Mapper::class, Constant_Array_Type_Mapper::class, Array_Type_Mapper::class, Boolean_Type_Mapper::class, Callable_Type_Mapper::class, Class_String_Type_Mapper::class, Closure_Type_Mapper::class, Conditional_Type_For_Parameter_Mapper::class, Conditional_Type_Mapper::class, Float_Type_Mapper::class, Generic_Class_String_Type_Mapper::class, Has_Method_Type_Mapper::class, Has_Offset_Type_Mapper::class, Has_Offset_Value_Type_Type_Mapper::class, Has_Property_Type_Mapper::class, Integer_Type_Mapper::class, Intersection_Type_Mapper::class, Iterable_Type_Mapper::class, Mixed_Type_Mapper::class, Never_Type_Mapper::class, Non_Empty_Array_Type_Mapper::class, Null_Type_Mapper::class, Object_Type_Mapper::class, Object_Without_Class_Type_Mapper::class, Oversized_Array_Type_Mapper::class, Parent_Static_Type_Mapper::class, Resource_Type_Mapper::class, Self_Object_Type_Mapper::class, Static_Type_Mapper::class, Strict_Mixed_Type_Mapper::class, String_Type_Mapper::class, This_Type_Mapper::class, Type_With_Class_Name_Type_Mapper::class, Union_Type_Mapper::class, Void_Type_Mapper::class];
    /**
     * @var array<class-string<PhpDocNodeDecoratorInterface>>
     */
    private const PHP_DOC_NODE_DECORATOR_CLASSES = [Const_Expr_Class_Name_Decorator::class, Doctrine_Annotation_Decorator::class, Array_Item_Class_Name_Decorator::class, Php_Doc_Tag_Generic_Uses_Decorator::class];
    /**
     * @var array<class-string>
     */
    private const PUBLIC_PHPSTAN_SERVICE_TYPES = [Scope_Factory::class, Type_Node_Resolver::class, Node_Scope_Resolver::class, Reflection_Provider::class];
    /**
     * @var array<class-string<OutputFormatterInterface>>
     */
    private const OUTPUT_FORMATTER_CLASSES = [Console_Output_Formatter::class, Json_Output_Formatter::class, Gitlab_Output_Formatter::class, J_Unit_Output_Formatter::class, Git_Hub_Output_Formatter::class];
    /**
     * @var array<class-string<NodeTypeResolverInterface>>
     */
    private const NODE_TYPE_RESOLVER_CLASSES = [Cast_Type_Resolver::class, Static_Call_Method_Call_Type_Resolver::class, Class_And_Interface_Type_Resolver::class, Identifier_Type_Resolver::class, Name_Type_Resolver::class, New_Type_Resolver::class, Param_Type_Resolver::class, Property_Fetch_Type_Resolver::class, Class_Const_Fetch_Type_Resolver::class, Property_Type_Resolver::class, Scalar_Type_Resolver::class, Trait_Type_Resolver::class];
    /**
     * @var array<class-string<PhpParserNodeMapperInterface>>
     */
    private const PHP_PARSER_NODE_MAPPER_CLASSES = [Fully_Qualified_Node_Mapper::class, Identifier_Node_Mapper::class, Intersection_Type_Node_Mapper::class, Name_Node_Mapper::class, Nullable_Type_Node_Mapper::class, String_Node_Mapper::class, Union_Type_Node_Mapper::class, Expr_Node_Mapper::class];
    /**
     * @var array<class-string<ConverterAttributeDecoratorInterface>>
     */
    private const CONVERTER_ATTRIBUTE_DECORATOR_CLASSES = [Sensio_Param_Converter_Attribute_Decorator::class, Doctrine_Converter_Attribute_Decorator::class];
    /**
     * @api used as next rectorConfig factory
     */
    public function create(): Rector_Config
    {
        $rector_config = new Rector_Config();
        $rector_config->import(__DIR__ . '/../../config/config.php');
        $rector_config->singleton(Application::class, static function (Container $container): Application {
            $console_application = $container->make(Console_Application::class);
            $command_names_to_hide = ['list', 'completion', 'help', 'worker'];
            foreach ($command_names_to_hide as $command_name_to_hide) {
                $command_to_hide = $console_application->get($command_name_to_hide);
                $command_to_hide->set_hidden();
            }
            return $console_application;
        });
        $rector_config->when(Console_Application::class)->needs('$commands')->give_tagged(Command::class);
        $rector_config->singleton(Inflector::class, static function (): Inflector {
            $inflector_factory = new Inflector_Factory();
            return $inflector_factory->build();
        });
        $rector_config->singleton(Configuration_Rule_Filter::class);
        $rector_config->singleton(Process_Command::class);
        $rector_config->singleton(Worker_Command::class);
        $rector_config->singleton(Setup_Ci_Command::class);
        $rector_config->singleton(List_Rules_Command::class);
        $rector_config->singleton(Custom_Rule_Command::class);
        $rector_config->when(List_Rules_Command::class)->needs('$rectors')->give_tagged(Rector_Interface::class);
        $rector_config->when(Only_Rule_Resolver::class)->needs('$rectors')->give_tagged(Rector_Interface::class);
        $rector_config->when(Deprecated_Rules_Reporter::class)->needs('$rectors')->give_tagged(Rector_Interface::class);
        $rector_config->singleton(File_Processor::class);
        $rector_config->singleton(Post_File_Processor::class);
        $rector_config->when(Rector_Node_Traverser::class)->needs('$rectors')->give_tagged(Rector_Interface::class);
        $rector_config->when(Config_Initializer::class)->needs('$rectors')->give_tagged(Rector_Interface::class);
        $rector_config->when(Class_Name_Import_Skipper::class)->needs('$classNameImportSkipVoters')->give_tagged(Class_Name_Import_Skip_Voter_Interface::class);
        $rector_config->singleton(Dynamic_Source_Locator_Provider::class, static function (Container $container): Dynamic_Source_Locator_Provider {
            $php_stan_services_factory = $container->make(Php_Stan_Services_Factory::class);
            return $php_stan_services_factory->create_dynamic_source_locator_provider();
        });
        // resettable
        $rector_config->tag(Dynamic_Source_Locator_Provider::class, Resettable_Interface::class);
        $rector_config->tag(Renamed_Classes_Data_Collector::class, Resettable_Interface::class);
        // caching
        $rector_config->singleton(Cache::class, static function (Container $container): Cache {
            /** @var CacheFactory $cacheFactory */
            $cache_factory = $container->make(Cache_Factory::class);
            return $cache_factory->create();
        });
        // tagged services
        $rector_config->when(Better_Php_Doc_Parser::class)->needs('$phpDocNodeDecorators')->give_tagged(Php_Doc_Node_Decorator_Interface::class);
        $rector_config->after_resolving(Array_Type_Mapper::class, static function (Array_Type_Mapper $array_type_mapper, Container $container): void {
            $array_type_mapper->autowire($container->make(Php_Stan_Static_Type_Mapper::class));
        });
        $rector_config->after_resolving(Conditional_Type_For_Parameter_Mapper::class, static function (Conditional_Type_For_Parameter_Mapper $conditional_type_for_parameter_mapper, Container $container): void {
            $php_stan_static_type_mapper = $container->make(Php_Stan_Static_Type_Mapper::class);
            $conditional_type_for_parameter_mapper->autowire($php_stan_static_type_mapper);
        });
        $rector_config->after_resolving(Conditional_Type_Mapper::class, static function (Conditional_Type_Mapper $conditional_type_mapper, Container $container): void {
            $php_stan_static_type_mapper = $container->make(Php_Stan_Static_Type_Mapper::class);
            $conditional_type_mapper->autowire($php_stan_static_type_mapper);
        });
        $rector_config->after_resolving(Union_Type_Mapper::class, static function (Union_Type_Mapper $union_type_mapper, Container $container): void {
            $php_stan_static_type_mapper = $container->make(Php_Stan_Static_Type_Mapper::class);
            $union_type_mapper->autowire($php_stan_static_type_mapper);
        });
        $rector_config->when(Php_Stan_Static_Type_Mapper::class)->needs('$typeMappers')->give_tagged(Type_Mapper_Interface::class);
        $rector_config->when(Php_Doc_Type_Mapper::class)->needs('$phpDocTypeMappers')->give_tagged(Php_Doc_Type_Mapper_Interface::class);
        $rector_config->when(Php_Parser_Node_Mapper::class)->needs('$phpParserNodeMappers')->give_tagged(Php_Parser_Node_Mapper_Interface::class);
        $rector_config->when(Node_Type_Resolver::class)->needs('$nodeTypeResolvers')->give_tagged(Node_Type_Resolver_Interface::class);
        // node name resolvers
        $rector_config->when(Node_Name_Resolver::class)->needs('$nodeNameResolvers')->give_tagged(Node_Name_Resolver_Interface::class);
        $rector_config->when(Attribute_Group_Named_Argument_Manipulator::class)->needs('$converterAttributeDecorators')->give_tagged(Converter_Attribute_Decorator_Interface::class);
        $this->register_tagged($rector_config, self::CONVERTER_ATTRIBUTE_DECORATOR_CLASSES, Converter_Attribute_Decorator_Interface::class);
        $rector_config->after_resolving(Abstract_Rector::class, static function (Abstract_Rector $rector, Container $container): void {
            $rector->autowire($container->get(Node_Name_Resolver::class), $container->get(Node_Type_Resolver::class), $container->get(Simple_Callable_Node_Traverser::class), $container->get(Node_Factory::class), $container->get(Skipper::class), $container->get(Node_Comparator::class), $container->get(Current_File_Provider::class), $container->get(Created_By_Rule_Decorator::class), $container->get(Changed_Node_Scope_Refresher::class), $container->get(Comments_Merger::class));
        });
        $this->register_tagged($rector_config, self::PHP_PARSER_NODE_MAPPER_CLASSES, Php_Parser_Node_Mapper_Interface::class);
        $this->register_tagged($rector_config, self::PHP_DOC_NODE_DECORATOR_CLASSES, Php_Doc_Node_Decorator_Interface::class);
        $this->register_tagged($rector_config, self::BASE_PHP_DOC_NODE_VISITORS, Base_Php_Doc_Node_Visitor_Interface::class);
        // PHP 8.0 attributes
        $this->register_tagged($rector_config, self::ANNOTATION_TO_ATTRIBUTE_MAPPER_CLASSES, Annotation_To_Attribute_Mapper_Interface::class);
        $this->register_tagged($rector_config, self::TYPE_MAPPER_CLASSES, Type_Mapper_Interface::class);
        $this->register_tagged($rector_config, self::PHPDOC_TYPE_MAPPER_CLASSES, Php_Doc_Type_Mapper_Interface::class);
        $this->register_tagged($rector_config, self::NODE_NAME_RESOLVER_CLASSES, Node_Name_Resolver_Interface::class);
        $this->register_tagged($rector_config, self::NODE_TYPE_RESOLVER_CLASSES, Node_Type_Resolver_Interface::class);
        $this->register_tagged($rector_config, self::OUTPUT_FORMATTER_CLASSES, Output_Formatter_Interface::class);
        $this->register_tagged($rector_config, self::BASE_PHP_DOC_NODE_VISITORS, Base_Php_Doc_Node_Visitor_Interface::class);
        $this->register_tagged($rector_config, self::CLASS_NAME_IMPORT_SKIPPER_CLASSES, Class_Name_Import_Skip_Voter_Interface::class);
        $rector_config->alias(Symfony_Style::class, Rector_Style::class);
        $rector_config->singleton(Symfony_Style::class, static function (Container $container): Symfony_Style {
            $symfony_style_factory = $container->make(Symfony_Style_Factory::class);
            return $symfony_style_factory->create();
        });
        $rector_config->when(Annotation_To_Attribute_Mapper::class)->needs('$annotationToAttributeMappers')->give_tagged(Annotation_To_Attribute_Mapper_Interface::class);
        $rector_config->when(Output_Formatter_Collector::class)->needs('$outputFormatters')->give_tagged(Output_Formatter_Interface::class);
        // required-like setter
        $rector_config->after_resolving(Array_Annotation_To_Attribute_Mapper::class, static function (Array_Annotation_To_Attribute_Mapper $array_annotation_to_attribute_mapper, Container $container): void {
            $annotation_to_attribute_mapper = $container->make(Annotation_To_Attribute_Mapper::class);
            $array_annotation_to_attribute_mapper->autowire($annotation_to_attribute_mapper);
        });
        $rector_config->after_resolving(Array_Item_Node_Annotation_To_Attribute_Mapper::class, static function (Array_Item_Node_Annotation_To_Attribute_Mapper $array_item_node_annotation_to_attribute_mapper, Container $container): void {
            $annotation_to_attribute_mapper = $container->make(Annotation_To_Attribute_Mapper::class);
            $array_item_node_annotation_to_attribute_mapper->autowire($annotation_to_attribute_mapper);
        });
        $rector_config->after_resolving(Plain_Value_Parser::class, static function (Plain_Value_Parser $plain_value_parser, Container $container): void {
            $plain_value_parser->autowire($container->make(Static_Doctrine_Annotation_Parser::class), $container->make(Array_Parser::class));
        });
        $rector_config->after_resolving(Curly_List_Node_Annotation_To_Attribute_Mapper::class, static function (Curly_List_Node_Annotation_To_Attribute_Mapper $curly_list_node_annotation_to_attribute_mapper, Container $container): void {
            $annotation_to_attribute_mapper = $container->make(Annotation_To_Attribute_Mapper::class);
            $curly_list_node_annotation_to_attribute_mapper->autowire($annotation_to_attribute_mapper);
        });
        $rector_config->after_resolving(Doctrine_Annotation_Annotation_To_Attribute_Mapper::class, static function (Doctrine_Annotation_Annotation_To_Attribute_Mapper $doctrine_annotation_annotation_to_attribute_mapper, Container $container): void {
            $annotation_to_attribute_mapper = $container->make(Annotation_To_Attribute_Mapper::class);
            $doctrine_annotation_annotation_to_attribute_mapper->autowire($annotation_to_attribute_mapper);
        });
        $rector_config->when(Php_Stan_Node_Scope_Resolver::class)->needs('$decoratingNodeVisitors')->give_tagged(Decorating_Node_Visitor_Interface::class);
        $this->register_tagged($rector_config, self::DECORATING_NODE_VISITOR_CLASSES, Decorating_Node_Visitor_Interface::class);
        $this->create_php_stan_services($rector_config);
        $rector_config->when(Php_Doc_Node_Mapper::class)->needs('$phpDocNodeVisitors')->give_tagged(Base_Php_Doc_Node_Visitor_Interface::class);
        // phpdoc-parser
        $rector_config->singleton(Parser_Config::class, static fn(Container $container): Parser_Config => new Parser_Config(['lines' => \true, 'indexes' => \true, 'comments' => \true]));
        return $rector_config;
    }
    /**
     * @param array<class-string> $classes
     * @param class-string $tagInterface
     */
    private function register_tagged(Container $container, array $classes, string $tag_interface): void
    {
        foreach ($classes as $class) {
            Assert::is_a_of($class, $tag_interface);
            $container->singleton($class);
            $container->tag($class, $tag_interface);
        }
    }
    private function create_php_stan_services(Rector_Config $rector_config): void
    {
        $rector_config->singleton(Parser::class, static function (Container $container) {
            $php_stan_services_factory = $container->make(Php_Stan_Services_Factory::class);
            return $php_stan_services_factory->create_php_stan_parser();
        });
        $rector_config->singleton(Lexer::class, static function (Container $container) {
            $php_stan_services_factory = $container->make(Php_Stan_Services_Factory::class);
            return $php_stan_services_factory->create_emulative_lexer();
        });
        foreach (self::PUBLIC_PHPSTAN_SERVICE_TYPES as $public_phpstan_service_type) {
            $rector_config->singleton($public_phpstan_service_type, static function (Container $container) use ($public_phpstan_service_type) {
                $php_stan_services_factory = $container->make(Php_Stan_Services_Factory::class);
                return $php_stan_services_factory->get_by_type($public_phpstan_service_type);
            });
        }
    }
}