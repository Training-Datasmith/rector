<?php

declare (strict_types=1);
namespace Rector\Differ;

use Rector_Prefix202603\Sebastian_Bergmann\Diff\Differ;
use Rector_Prefix202603\Sebastian_Bergmann\Diff\Output\Strict_Unified_Diff_Output_Builder;
final class Default_Differ
{
    /**
     * @readonly
     */
    private Differ $differ;
    public function __construct()
    {
        $strict_unified_diff_output_builder = new Strict_Unified_Diff_Output_Builder(['fromFile' => 'Original', 'toFile' => 'New']);
        $this->differ = new Differ($strict_unified_diff_output_builder);
    }
    public function diff(string $old, string $new): string
    {
        return $this->differ->diff($old, $new);
    }
}