<?php

declare (strict_types=1);
namespace Rector\Post_Rector\Rector;

use Php_Parser\Node;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node_Visitor;
use Rector\Coding_Style\Application\Use_Imports_Adder;
use Rector\Node_Type_Resolver\Php_Stan\Type\Type_Factory;
use Rector\Php_Parser\Node\File_Node;
use Rector\Post_Rector\Collector\Use_Nodes_To_Add_Collector;
use Rector\Static_Type_Mapper\Value_Object\Type\Fully_Qualified_Object_Type;
final class Use_Adding_Post_Rector extends \Rector\Post_Rector\Rector\Abstract_Post_Rector
{
    /**
     * @readonly
     */
    private Type_Factory $type_factory;
    /**
     * @readonly
     */
    private Use_Imports_Adder $use_imports_adder;
    /**
     * @readonly
     */
    private Use_Nodes_To_Add_Collector $use_nodes_to_add_collector;
    public function __construct(Type_Factory $type_factory, Use_Imports_Adder $use_imports_adder, Use_Nodes_To_Add_Collector $use_nodes_to_add_collector)
    {
        $this->type_factory = $type_factory;
        $this->use_imports_adder = $use_imports_adder;
        $this->use_nodes_to_add_collector = $use_nodes_to_add_collector;
    }
    /**
     * @param Stmt[] $nodes
     * @return Stmt[]
     */
    public function before_traverse(array $nodes): array
    {
        // no nodes → just return
        if ($nodes === []) {
            return $nodes;
        }
        $root_node = $this->resolve_root_node($nodes);
        if (!$root_node instanceof File_Node && !$root_node instanceof Namespace_) {
            return $nodes;
        }
        $use_import_types = $this->use_nodes_to_add_collector->get_object_imports_by_file_path($this->get_file()->get_file_path());
        $constant_use_import_types = $this->use_nodes_to_add_collector->get_constant_imports_by_file_path($this->get_file()->get_file_path());
        $function_use_import_types = $this->use_nodes_to_add_collector->get_function_imports_by_file_path($this->get_file()->get_file_path());
        if ($use_import_types === [] && $constant_use_import_types === [] && $function_use_import_types === []) {
            return $nodes;
        }
        /** @var FullyQualifiedObjectType[] $useImportTypes */
        $use_import_types = $this->type_factory->uniquate_types($use_import_types);
        $stmts = $root_node instanceof File_Node ? $root_node->stmts : $nodes;
        if ($this->process_stmts_with_imported_uses($stmts, $use_import_types, $constant_use_import_types, $function_use_import_types, $root_node)) {
            $this->add_rector_class_with_line($root_node);
        }
        return $nodes;
    }
    public function enter_node(Node $node): int
    {
        /**
         * We stop the traversal because all the work has already been done in the beforeTraverse() function
         *
         * Using STOP_TRAVERSAL is usually dangerous as it will stop the processing of all your nodes for all visitors
         * but since the PostFileProcessor is using direct new NodeTraverser() and traverse() for only a single
         * visitor per execution, using stop traversal here is safe,
         * ref https://github.com/rectorphp/rector-src/blob/fc1e742fa4d9861ccdc5933f3b53613b8223438d/src/PostRector/Application/PostFileProcessor.php#L59-L61
         */
        return Node_Visitor::STOP_TRAVERSAL;
    }
    /**
     * @param Stmt[] $stmts
     * @param FullyQualifiedObjectType[] $useImportTypes
     * @param FullyQualifiedObjectType[] $constantUseImportTypes
     * @param FullyQualifiedObjectType[] $functionUseImportTypes
     * @param \Rector\PhpParser\Node\FileNode|\PhpParser\Node\Stmt\Namespace_ $namespace
     */
    private function process_stmts_with_imported_uses(array $stmts, array $use_import_types, array $constant_use_import_types, array $function_use_import_types, $namespace): bool
    {
        // A. has namespace? add under it
        if ($namespace instanceof Namespace_) {
            // then add, to prevent adding + removing false positive of same short use
            return $this->use_imports_adder->add_imports_to_namespace($namespace, $use_import_types, $constant_use_import_types, $function_use_import_types);
        }
        // B. no namespace? add in the top
        $use_import_types = $this->filter_out_non_namespaced_names($use_import_types);
        // then add, to prevent adding + removing false positive of same short use
        return $this->use_imports_adder->add_imports_to_stmts($namespace, $stmts, $use_import_types, $constant_use_import_types, $function_use_import_types);
    }
    /**
     * Prevents
     * @param FullyQualifiedObjectType[] $useImportTypes
     * @return FullyQualifiedObjectType[]
     */
    private function filter_out_non_namespaced_names(array $use_import_types): array
    {
        $namespaced_use_import_types = [];
        foreach ($use_import_types as $use_import_type) {
            if (strpos($use_import_type->get_class_name(), '\\') === \false) {
                continue;
            }
            $namespaced_use_import_types[] = $use_import_type;
        }
        return $namespaced_use_import_types;
    }
    /**
     * @param Stmt[] $nodes
     * @return \PhpParser\Node\Stmt\Namespace_|\Rector\PhpParser\Node\FileNode|null
     */
    private function resolve_root_node(array $nodes)
    {
        if ($nodes === []) {
            return null;
        }
        $first_stmt = $nodes[0];
        if (!$first_stmt instanceof File_Node) {
            return null;
        }
        foreach ($first_stmt->stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                return $stmt;
            }
        }
        return $first_stmt;
    }
}