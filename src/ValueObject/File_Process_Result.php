<?php

declare (strict_types=1);
namespace Rector\Value_Object;

use Rector\Value_Object\Error\System_Error;
use Rector\Value_Object\Reporting\File_Diff;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class File_Process_Result
{
    /**
     * @var SystemError[]
     * @readonly
     */
    private array $system_errors;
    /**
     * @readonly
     */
    private ?File_Diff $file_diff;
    /**
     * @readonly
     */
    private bool $has_changed;
    /**
     * @param SystemError[] $systemErrors
     */
    public function __construct(array $system_errors, ?File_Diff $file_diff, bool $has_changed)
    {
        $this->system_errors = $system_errors;
        $this->file_diff = $file_diff;
        $this->has_changed = $has_changed;
        Assert::all_is_instance_of($system_errors, System_Error::class);
    }
    /**
     * @return SystemError[]
     */
    public function get_system_errors(): array
    {
        return $this->system_errors;
    }
    public function get_file_diff(): ?File_Diff
    {
        return $this->file_diff;
    }
    public function has_changed(): bool
    {
        return $this->has_changed;
    }
}