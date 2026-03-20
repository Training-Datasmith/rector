<?php

declare (strict_types=1);
namespace Rector\Reporting;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Configuration\Vendor_Miss_Analyse_Guard;
use Rector\Post_Rector\Contract\Rector\Post_Rector_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class Miss_Configuration_Reporter
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @readonly
     */
    private Vendor_Miss_Analyse_Guard $vendor_miss_analyse_guard;
    public function __construct(Symfony_Style $symfony_style, Vendor_Miss_Analyse_Guard $vendor_miss_analyse_guard)
    {
        $this->symfony_style = $symfony_style;
        $this->vendor_miss_analyse_guard = $vendor_miss_analyse_guard;
    }
    public function report_skipped_never_registered_rules(): void
    {
        $registered_rules = Simple_Parameter_Provider::provide_array_parameter(Option::REGISTERED_RECTOR_RULES);
        $skipped_rules = Simple_Parameter_Provider::provide_array_parameter(Option::SKIPPED_RECTOR_RULES);
        $never_registered_skipped_rules = array_unique(array_diff($skipped_rules, $registered_rules));
        // remove special PostRectorInterface rules, they are registered in a different way
        $never_registered_skipped_rules = array_filter($never_registered_skipped_rules, fn($skipped_rule): bool => !is_a($skipped_rule, Post_Rector_Interface::class, \true));
        if ($never_registered_skipped_rules === []) {
            return;
        }
        $this->symfony_style->warning(sprintf('%s never registered. You can remove %s from "->withSkip()"', count($never_registered_skipped_rules) > 1 ? 'These skipped rules are' : 'This skipped rule is', count($never_registered_skipped_rules) > 1 ? 'them' : 'it'));
        $this->symfony_style->listing($never_registered_skipped_rules);
    }
    /**
     * @param string[] $filePaths
     */
    public function report_vendor_in_paths(array $file_paths): void
    {
        if (!$this->vendor_miss_analyse_guard->is_vendor_analyzed($file_paths)) {
            return;
        }
        $this->symfony_style->warning(sprintf('Rector has detected a "/vendor" directory in your configured paths. If this is Composer\'s vendor directory, this is not necessary as it will be autoloaded. Scanning the Composer /vendor directory will cause Rector to run much slower and possibly with errors.%sRemove "/vendor" from Rector paths and run again.', "\n\n"));
        sleep(3);
    }
    public function report_start_with_short_open_tag(): void
    {
        $files = Simple_Parameter_Provider::provide_array_parameter(Option::SKIPPED_START_WITH_SHORT_OPEN_TAG_FILES);
        if ($files === []) {
            return;
        }
        $suffix = count($files) > 1 ? 's were' : ' was';
        $file_list = implode("\n", $files);
        $this->symfony_style->warning(sprintf('The following file%s skipped as starting with short open tag. Migrate to long open PHP tag first: %s%s', $suffix, "\n\n", $file_list));
        sleep(3);
    }
}