<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator_Provider;

use Php_Stan\Better_Reflection\Source_Locator\Type\Aggregate_Source_Locator;
use Php_Stan\Better_Reflection\Source_Locator\Type\Source_Locator;
use Php_Stan\Reflection\Better_Reflection\Source_Locator\Optimized_Directory_Source_Locator_Factory;
use Php_Stan\Reflection\Better_Reflection\Source_Locator\Optimized_Single_File_Source_Locator_Repository;
use Rector\Contract\Dependency_Injection\Resettable_Interface;
use Rector\Testing\Php_Unit\Static_Php_Unit_Environment;
/**
 * @api phpstan external
 */
final class Dynamic_Source_Locator_Provider implements Resettable_Interface
{
    /**
     * @readonly
     */
    private Optimized_Directory_Source_Locator_Factory $optimized_directory_source_locator_factory;
    /**
     * @readonly
     */
    private Optimized_Single_File_Source_Locator_Repository $optimized_single_file_source_locator_repository;
    /**
     * @var string[]
     */
    private array $file_paths = [];
    /**
     * @var string[]
     */
    private array $directories = [];
    private ?Aggregate_Source_Locator $aggregate_source_locator = null;
    public function __construct(Optimized_Directory_Source_Locator_Factory $optimized_directory_source_locator_factory, Optimized_Single_File_Source_Locator_Repository $optimized_single_file_source_locator_repository)
    {
        $this->optimized_directory_source_locator_factory = $optimized_directory_source_locator_factory;
        $this->optimized_single_file_source_locator_repository = $optimized_single_file_source_locator_repository;
    }
    public function set_file_path(string $file_path): void
    {
        $this->file_paths = [$file_path];
    }
    /**
     * @param string[] $files
     */
    public function add_files(array $files): void
    {
        $this->file_paths = array_unique(array_merge($this->file_paths, $files));
    }
    /**
     * @param string[] $directories
     */
    public function add_directories(array $directories): void
    {
        $this->directories = array_unique(array_merge($this->directories, $directories));
    }
    public function provide(): Source_Locator
    {
        // do not cache for PHPUnit, as in test every fixture is different
        $is_php_unit_run = Static_Php_Unit_Environment::is_php_unit_run();
        if ($this->aggregate_source_locator instanceof Aggregate_Source_Locator && !$is_php_unit_run) {
            return $this->aggregate_source_locator;
        }
        $source_locators = [];
        foreach ($this->file_paths as $file) {
            $source_locators[] = $this->optimized_single_file_source_locator_repository->get_or_create($file);
        }
        foreach ($this->directories as $directory) {
            $source_locators[] = $this->optimized_directory_source_locator_factory->create_by_directory($directory);
        }
        return $this->aggregate_source_locator = new Aggregate_Source_Locator($source_locators);
    }
    public function are_paths_empty(): bool
    {
        return $this->file_paths === [] && $this->directories === [];
    }
    /**
     * @api to allow fast single-container tests
     */
    public function reset(): void
    {
        $this->file_paths = [];
        $this->directories = [];
        $this->aggregate_source_locator = null;
    }
}