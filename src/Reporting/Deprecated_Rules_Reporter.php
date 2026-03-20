<?php

declare (strict_types=1);
namespace Rector\Reporting;

use Rector\Configuration\Deprecation\Contract\Deprecated_Interface;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Contract\Php_Parser\Node\Stmts_Aware_Interface;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Php_Parser\Enum\Node_Group;
use Rector\Php_Parser\Node\Custom_Node\File_Without_Namespace;
use Rector\Php_Parser\Node\File_Node;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use ReflectionMethod;
final class Deprecated_Rules_Reporter
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @var RectorInterface[]
     * @readonly
     */
    private array $rectors;
    /**
     * @param RectorInterface[] $rectors
     */
    public function __construct(Symfony_Style $symfony_style, array $rectors)
    {
        $this->symfony_style = $symfony_style;
        $this->rectors = $rectors;
    }
    public function report_deprecated_rules(): void
    {
        /** @var string[] $registeredRectorRules */
        $registered_rector_rules = Simple_Parameter_Provider::provide_array_parameter(Option::REGISTERED_RECTOR_RULES);
        foreach ($registered_rector_rules as $registered_rector_rule) {
            if (!is_a($registered_rector_rule, Deprecated_Interface::class, \true)) {
                continue;
            }
            $this->symfony_style->warning(sprintf('Registered rule "%s" is deprecated and will be removed. Upgrade your config to use another rule or remove it', $registered_rector_rule));
        }
    }
    public function report_deprecated_skipped_rules(): void
    {
        /** @var string[] $skippedRectorRules */
        $skipped_rector_rules = Simple_Parameter_Provider::provide_array_parameter(Option::SKIPPED_RECTOR_RULES);
        foreach ($skipped_rector_rules as $skipped_rector_rule) {
            if (!is_a($skipped_rector_rule, Deprecated_Interface::class, \true)) {
                continue;
            }
            $this->symfony_style->warning(sprintf('Skipped rule "%s" is deprecated', $skipped_rector_rule));
        }
    }
    public function report_deprecated_rector_unsupported_methods(): void
    {
        // to be added in related PR
        if (!class_exists(File_Node::class)) {
            return;
        }
        foreach ($this->rectors as $rector) {
            $before_traverse_method_reflection = new ReflectionMethod($rector, 'beforeTraverse');
            if (\PHP_VERSION_ID < 80100) {
                $before_traverse_method_reflection->set_accessible(\true);
            }
            if ($before_traverse_method_reflection->get_declaring_class()->get_name() === get_class($rector)) {
                $this->symfony_style->warning(sprintf('Rector rule "%s" uses deprecated "beforeTraverse" method. It should not be used, as will be marked as final. Not part of RectorInterface contract. Use "%s" to hook into file-level changes instead.', get_class($rector), File_Node::class));
            }
        }
    }
    public function report_deprecated_node_types(): void
    {
        // helper property to avoid reporting multiple times
        static $reported_classes = [];
        foreach ($this->rectors as $rector) {
            if (in_array(File_Without_Namespace::class, $rector->get_node_types(), \true)) {
                $this->report_deprecated_file_without_namespace($rector);
                continue;
            }
            if (!in_array(Stmts_Aware_Interface::class, $rector->get_node_types())) {
                continue;
            }
            // already reported, skip
            if (in_array(get_class($rector), $reported_classes, \true)) {
                continue;
            }
            $reported_classes[] = get_class($rector);
            $this->symfony_style->warning(sprintf('Rector rule "%s" uses StmtsAwareInterface that is now deprecated.%sUse "%s::%s" instead.%sSee %s for more', get_class($rector), \PHP_EOL, Node_Group::class, 'STMTS_AWARE', \PHP_EOL . \PHP_EOL, 'https://github.com/rectorphp/rector-src/pull/7679'));
        }
    }
    private function report_deprecated_file_without_namespace(Rector_Interface $rector): void
    {
        $this->symfony_style->warning(sprintf('Node type "%s" is deprecated and will be removed. Use "%s" in the "%s" rule instead instead.%sSee %s for upgrade path', File_Without_Namespace::class, File_Node::class, get_class($rector), \PHP_EOL . \PHP_EOL, 'https://github.com/rectorphp/rector-src/blob/main/UPGRADING.md'));
    }
}