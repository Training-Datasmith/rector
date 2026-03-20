<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Dependency_Injection;

use Php_Parser\Lexer;
use Php_Stan\Analyser\Node_Scope_Resolver;
use Php_Stan\Analyser\Scope_Factory;
use Php_Stan\Dependency_Injection\Container;
use Php_Stan\Dependency_Injection\Container_Factory;
use Php_Stan\File\File_Helper;
use Php_Stan\Parser\Parser;
use Php_Stan\Php_Doc\Type_Node_Resolver;
use Php_Stan\Reflection\Reflection_Provider;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator_Provider\Dynamic_Source_Locator_Provider;
use Rector_Prefix202603\Symfony\Component\Console\Input\Array_Input;
use Rector_Prefix202603\Symfony\Component\Console\Output\Console_Output;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use Rector_Prefix202603\Webmozart\Assert\Assert;
use Throwable;
/**
 * Factory so Symfony app can use services from PHPStan container
 *
 * @see \Rector\NodeTypeResolver\DependencyInjection\PHPStanServicesFactory
 */
final class Php_Stan_Services_Factory
{
    /**
     * @var string
     */
    private const INVALID_BLEEDING_EDGE_PATH_MESSAGE = <<<MESSAGE_ERROR
    '%s, use full path bleedingEdge.neon config, eg:
    
    includes:
        - phar://vendor/phpstan/phpstan/phpstan.phar/conf/bleedingEdge.neon
    
    in your included phpstan configuration.
    
    MESSAGE_ERROR;
    /**
     * @readonly
     */
    private Container $container;
    public function __construct()
    {
        $container_factory = new Container_Factory(getcwd());
        $additional_config_files = $this->resolve_additional_config_files();
        try {
            $this->container = $container_factory->create(Simple_Parameter_Provider::provide_string_parameter(Option::CONTAINER_CACHE_DIRECTORY), $additional_config_files, []);
        } catch (Throwable $throwable) {
            if ($throwable->get_message() === "File 'phar://phpstan.phar/conf/bleedingEdge.neon' is missing or is not readable.") {
                $symfony_style = new Symfony_Style(new Array_Input([]), new Console_Output());
                $symfony_style->error(str_replace("\r\n", "\n", sprintf(self::INVALID_BLEEDING_EDGE_PATH_MESSAGE, $throwable->get_message())));
                exit(-1);
            }
            throw $throwable;
        }
    }
    /**
     * @api
     */
    public function create_reflection_provider(): Reflection_Provider
    {
        return $this->container->get_by_type(Reflection_Provider::class);
    }
    /**
     * @api
     */
    public function create_emulative_lexer(): Lexer
    {
        return $this->container->get_service('currentPhpVersionLexer');
    }
    /**
     * @api
     */
    public function create_php_stan_parser(): Parser
    {
        return $this->container->get_service('currentPhpVersionRichParser');
    }
    /**
     * @api
     */
    public function create_node_scope_resolver(): Node_Scope_Resolver
    {
        return $this->container->get_by_type(Node_Scope_Resolver::class);
    }
    /**
     * @api
     */
    public function create_scope_factory(): Scope_Factory
    {
        return $this->container->get_by_type(Scope_Factory::class);
    }
    /**
     * @template TObject as Object
     *
     * @param class-string<TObject> $type
     * @return TObject
     */
    public function get_by_type(string $type): object
    {
        return $this->container->get_by_type($type);
    }
    /**
     * @api
     */
    public function create_file_helper(): File_Helper
    {
        return $this->container->get_by_type(File_Helper::class);
    }
    /**
     * @api
     */
    public function create_type_node_resolver(): Type_Node_Resolver
    {
        return $this->container->get_by_type(Type_Node_Resolver::class);
    }
    /**
     * @api
     */
    public function create_dynamic_source_locator_provider(): Dynamic_Source_Locator_Provider
    {
        return $this->container->get_by_type(Dynamic_Source_Locator_Provider::class);
    }
    /**
     * @return string[]
     */
    private function resolve_additional_config_files(): array
    {
        $additional_config_files = [];
        if (Simple_Parameter_Provider::has_parameter(Option::PHPSTAN_FOR_RECTOR_PATHS)) {
            $paths = Simple_Parameter_Provider::provide_array_parameter(Option::PHPSTAN_FOR_RECTOR_PATHS);
            foreach ($paths as $path) {
                Assert::string($path);
                $additional_config_files[] = $path;
            }
        }
        $additional_config_files[] = __DIR__ . '/../../../config/phpstan/static-reflection.neon';
        $additional_config_files[] = __DIR__ . '/../../../config/phpstan/better-infer.neon';
        $additional_config_files[] = __DIR__ . '/../../../config/phpstan/parser.neon';
        return array_filter($additional_config_files, \Closure::from_callable('file_exists'));
    }
}