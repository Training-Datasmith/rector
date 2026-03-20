<?php

declare (strict_types=1);
namespace Rector\Value_Object;

use Rector\Value_Object\Error\System_Error;
use Rector\Value_Object\Reporting\File_Diff;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Process_Result
{
    /**
     * @var SystemError[]
     */
    private array $system_errors;
    /**
     * @var FileDiff[]
     * @readonly
     */
    private array $file_diffs;
    /**
     * @readonly
     */
    private int $total_changed;
    /**
     * @param SystemError[] $systemErrors
     * @param FileDiff[] $fileDiffs
     */
    public function __construct(array $system_errors, array $file_diffs, int $total_changed)
    {
        $this->system_errors = $system_errors;
        $this->file_diffs = $file_diffs;
        $this->total_changed = $total_changed;
        Assert::all_is_instance_of($system_errors, System_Error::class);
        Assert::all_is_instance_of($file_diffs, File_Diff::class);
    }
    /**
     * @return SystemError[]
     */
    public function get_system_errors(): array
    {
        return $this->system_errors;
    }
    /**
     * @return FileDiff[]
     */
    public function get_file_diffs(bool $only_with_changes = \true): array
    {
        if ($only_with_changes) {
            return array_filter($this->file_diffs, fn(File_Diff $file_diff): bool => $file_diff->get_diff() !== '');
        }
        return $this->file_diffs;
    }
    /**
     * @param SystemError[] $systemErrors
     */
    public function add_system_errors(array $system_errors): void
    {
        Assert::all_is_instance_of($system_errors, System_Error::class);
        $this->system_errors = array_merge($this->system_errors, $system_errors);
    }
    public function get_total_changed(): int
    {
        return $this->total_changed;
    }
}