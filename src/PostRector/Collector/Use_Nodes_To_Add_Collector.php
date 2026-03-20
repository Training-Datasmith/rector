<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Collector;

use Php_Parser\Node\Identifier;
use Rector\Application\Provider\Current_File_Provider;
use Rector\Naming\Naming\Use_Imports_Resolver;
use Rector\Static_Type_Mapper\Value_Object\Type\Aliased_Object_Type;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
use Rector\Value_Object\Application\File;
final class Use_Nodes_To_Add_Collector
{
    /**
     * @readonly
     */
    private Current_File_Provider $current_file_provider;
    /**
     * @readonly
     */
    private Use_Imports_Resolver $use_imports_resolver;
    /**
     * @var array<string, FullyQualifiedObjectType[]>
     */
    private array $constant_use_import_types_in_file_path = [];
    /**
     * @var array<string, FullyQualifiedObjectType[]>
     */
    private array $function_use_import_types_in_file_path = [];
    /**
     * @var array<string, FullyQualifiedObjectType[]>
     */
    private array $use_import_types_in_file_path = [];
    public function __construct(Current_File_Provider $current_file_provider, Use_Imports_Resolver $use_imports_resolver)
    {
        $this->current_file_provider = $current_file_provider;
        $this->use_imports_resolver = $use_imports_resolver;
    }
    public function add_use_import(Fully_Qualified_Object_Type $fully_qualified_object_type): void
    {
        // @todo consider using FileNode directly
        /** @var File $file */
        $file = $this->current_file_provider->get_file();
        $this->use_import_types_in_file_path[$file->get_file_path()][] = $fully_qualified_object_type;
    }
    public function add_constant_use_import(Fully_Qualified_Object_Type $fully_qualified_object_type): void
    {
        /** @var File $file */
        $file = $this->current_file_provider->get_file();
        $this->constant_use_import_types_in_file_path[$file->get_file_path()][] = $fully_qualified_object_type;
    }
    public function add_function_use_import(Fully_Qualified_Object_Type $fully_qualified_object_type): void
    {
        /** @var File $file */
        $file = $this->current_file_provider->get_file();
        $this->function_use_import_types_in_file_path[$file->get_file_path()][] = $fully_qualified_object_type;
    }
    /**
     * @return AliasedObjectType[]|FullyQualifiedObjectType[]
     */
    public function get_use_import_types_by_node(File $file): array
    {
        $file_path = $file->get_file_path();
        $object_types = $this->use_import_types_in_file_path[$file_path] ?? [];
        $uses = $this->use_imports_resolver->resolve();
        foreach ($uses as $use) {
            $prefix = $this->use_imports_resolver->resolve_prefix($use);
            foreach ($use->uses as $use_use) {
                if ($use_use->alias instanceof Identifier) {
                    $object_types[] = new Aliased_Object_Type($use_use->alias->to_string(), $prefix . $use_use->name);
                } else {
                    $object_types[] = new Fully_Qualified_Object_Type($prefix . $use_use->name);
                }
            }
        }
        return $object_types;
    }
    public function has_import(File $file, Fully_Qualified_Object_Type $fully_qualified_object_type): bool
    {
        $use_imports = $this->get_use_import_types_by_node($file);
        foreach ($use_imports as $use_import) {
            if ($use_import->equals($fully_qualified_object_type)) {
                return \true;
            }
        }
        return \false;
    }
    public function is_short_imported(File $file, Fully_Qualified_Object_Type $fully_qualified_object_type): bool
    {
        $short_name = $fully_qualified_object_type->get_short_name();
        $file_path = $file->get_file_path();
        $file_constant_use_import_types = $this->constant_use_import_types_in_file_path[$file_path] ?? [];
        foreach ($file_constant_use_import_types as $file_constant_use_import_type) {
            // don't compare strtolower for use const as insensitive is allowed, see https://3v4l.org/lteVa
            if ($file_constant_use_import_type->get_short_name() === $short_name) {
                return \true;
            }
        }
        $short_name = strtolower($short_name);
        if ($this->is_short_class_imported($file_path, $short_name)) {
            return \true;
        }
        $file_function_use_import_types = $this->function_use_import_types_in_file_path[$file_path] ?? [];
        foreach ($file_function_use_import_types as $file_function_use_import_type) {
            if (strtolower($file_function_use_import_type->get_short_name()) === $short_name) {
                return \true;
            }
        }
        return \false;
    }
    public function is_import_shortable(File $file, Fully_Qualified_Object_Type $fully_qualified_object_type): bool
    {
        $file_path = $file->get_file_path();
        $file_use_import_types = $this->use_import_types_in_file_path[$file_path] ?? [];
        foreach ($file_use_import_types as $file_use_import_type) {
            if ($fully_qualified_object_type->equals($file_use_import_type)) {
                return \true;
            }
        }
        $constant_imports = $this->constant_use_import_types_in_file_path[$file_path] ?? [];
        foreach ($constant_imports as $constant_import) {
            if ($fully_qualified_object_type->equals($constant_import)) {
                return \true;
            }
        }
        $function_imports = $this->function_use_import_types_in_file_path[$file_path] ?? [];
        foreach ($function_imports as $function_import) {
            if ($fully_qualified_object_type->equals($function_import)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @return AliasedObjectType[]|FullyQualifiedObjectType[]
     */
    public function get_object_imports_by_file_path(string $file_path): array
    {
        return $this->use_import_types_in_file_path[$file_path] ?? [];
    }
    /**
     * @return FullyQualifiedObjectType[]
     */
    public function get_constant_imports_by_file_path(string $file_path): array
    {
        return $this->constant_use_import_types_in_file_path[$file_path] ?? [];
    }
    /**
     * @return FullyQualifiedObjectType[]
     */
    public function get_function_imports_by_file_path(string $file_path): array
    {
        return $this->function_use_import_types_in_file_path[$file_path] ?? [];
    }
    private function is_short_class_imported(string $file_path, string $short_name): bool
    {
        $file_use_imports = $this->use_import_types_in_file_path[$file_path] ?? [];
        foreach ($file_use_imports as $file_use_import) {
            if (strtolower($file_use_import->get_short_name()) === $short_name) {
                return \true;
            }
        }
        return \false;
    }
}