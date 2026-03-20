<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Application;

use Php_Parser\Node\Stmt;
use Php_Parser\Node_Traverser;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Configuration\Renamed_Classes_Data_Collector;
use Rector\Contract\Dependency_Injection\Resettable_Interface;
use Rector\Post_Rector\Contract\Rector\Post_Rector_Interface;
use Rector\Post_Rector\Rector\Class_Renaming_Post_Rector;
use Rector\Post_Rector\Rector\Docblock_Name_Importing_Post_Rector;
use Rector\Post_Rector\Rector\Name_Importing_Post_Rector;
use Rector\Post_Rector\Rector\Unused_Import_Removing_Post_Rector;
use Rector\Post_Rector\Rector\Use_Adding_Post_Rector;
use Rector\Renaming\Rector\Name\Rename_Class_Rector;
use Rector\Skipper\Skipper\Skipper;
use Rector\Value_Object\Application\File;
final class Post_File_Processor implements Resettable_Interface
{
    /**
     * @readonly
     */
    private Skipper $skipper;
    /**
     * @readonly
     */
    private Use_Adding_Post_Rector $use_adding_post_rector;
    /**
     * @readonly
     */
    private Name_Importing_Post_Rector $name_importing_post_rector;
    /**
     * @readonly
     */
    private Class_Renaming_Post_Rector $class_renaming_post_rector;
    /**
     * @readonly
     */
    private Docblock_Name_Importing_Post_Rector $docblock_name_importing_post_rector;
    /**
     * @readonly
     */
    private Unused_Import_Removing_Post_Rector $unused_import_removing_post_rector;
    /**
     * @readonly
     */
    private Renamed_Classes_Data_Collector $renamed_classes_data_collector;
    /**
     * @var PostRectorInterface[]
     */
    private array $post_rectors = [];
    public function __construct(Skipper $skipper, Use_Adding_Post_Rector $use_adding_post_rector, Name_Importing_Post_Rector $name_importing_post_rector, Class_Renaming_Post_Rector $class_renaming_post_rector, Docblock_Name_Importing_Post_Rector $docblock_name_importing_post_rector, Unused_Import_Removing_Post_Rector $unused_import_removing_post_rector, Renamed_Classes_Data_Collector $renamed_classes_data_collector)
    {
        $this->skipper = $skipper;
        $this->use_adding_post_rector = $use_adding_post_rector;
        $this->name_importing_post_rector = $name_importing_post_rector;
        $this->class_renaming_post_rector = $class_renaming_post_rector;
        $this->docblock_name_importing_post_rector = $docblock_name_importing_post_rector;
        $this->unused_import_removing_post_rector = $unused_import_removing_post_rector;
        $this->renamed_classes_data_collector = $renamed_classes_data_collector;
    }
    public function reset(): void
    {
        $this->post_rectors = [];
    }
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function traverse(array $stmts, File $file): array
    {
        foreach ($this->get_post_rectors() as $post_rector) {
            // file must be set early into PostRector class to ensure its usage
            // always match on skipping process
            $post_rector->set_file($file);
            if ($this->should_skip_post_rector($post_rector, $file->get_file_path(), $stmts)) {
                continue;
            }
            $node_traverser = new Node_Traverser($post_rector);
            $stmts = $node_traverser->traverse($stmts);
        }
        return $stmts;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function should_skip_post_rector(Post_Rector_Interface $post_rector, string $file_path, array $stmts): bool
    {
        if ($this->skipper->should_skip_element_and_file_path($post_rector, $file_path)) {
            return \true;
        }
        // skip renaming if rename class rector is skipped
        if ($post_rector instanceof Class_Renaming_Post_Rector && $this->skipper->should_skip_element_and_file_path(Rename_Class_Rector::class, $file_path)) {
            return \true;
        }
        return !$post_rector->should_traverse($stmts);
    }
    /**
     * Lazy load, to enable test reset with different configuration
     * @return PostRectorInterface[]
     */
    private function get_post_rectors(): array
    {
        if ($this->post_rectors !== []) {
            return $this->post_rectors;
        }
        $is_renamed_class_enabled = $this->renamed_classes_data_collector->get_old_to_new_classes() !== [];
        $is_name_importing_enabled = Simple_Parameter_Provider::provide_bool_parameter(Option::AUTO_IMPORT_NAMES);
        $is_removing_unused_imports_enabled = Simple_Parameter_Provider::provide_bool_parameter(Option::REMOVE_UNUSED_IMPORTS);
        $post_rectors = [];
        // sorted by priority, to keep removed imports in order
        if ($is_renamed_class_enabled && $is_name_importing_enabled) {
            $post_rectors[] = $this->class_renaming_post_rector;
        }
        // import names
        if ($is_name_importing_enabled) {
            $post_rectors[] = $this->name_importing_post_rector;
            // import docblocks
            if (Simple_Parameter_Provider::provide_bool_parameter(Option::AUTO_IMPORT_DOC_BLOCK_NAMES)) {
                $post_rectors[] = $this->docblock_name_importing_post_rector;
            }
        }
        $post_rectors[] = $this->use_adding_post_rector;
        if ($is_removing_unused_imports_enabled) {
            $post_rectors[] = $this->unused_import_removing_post_rector;
        }
        $this->post_rectors = $post_rectors;
        return $this->post_rectors;
    }
}