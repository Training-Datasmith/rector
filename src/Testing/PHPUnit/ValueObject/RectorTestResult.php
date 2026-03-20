<?php

declare (strict_types=1);
namespace Rector\Testing\Php_Unit\Value_Object;

use Rector\Contract\Rector\Rector_Interface;
use Rector\Util\Rector_Classes_Sorter;
use Rector\Value_Object\Process_Result;
/**
 * @api used in tests
 */
final class Rector_Test_Result
{
    /**
     * @readonly
     */
    private string $changed_contents;
    /**
     * @readonly
     */
    private Process_Result $process_result;
    public function __construct(string $changed_contents, Process_Result $process_result)
    {
        $this->changed_contents = $changed_contents;
        $this->process_result = $process_result;
    }
    public function get_changed_contents(): string
    {
        return $this->changed_contents;
    }
    /**
     * @return array<class-string<RectorInterface>>
     */
    public function get_applied_rector_classes(): array
    {
        $rector_classes = [];
        foreach ($this->process_result->get_file_diffs(\false) as $file_diff) {
            $rector_classes = array_merge($rector_classes, $file_diff->get_rector_classes());
        }
        return Rector_Classes_Sorter::sort_and_filter_out_post_rectors($rector_classes);
    }
}