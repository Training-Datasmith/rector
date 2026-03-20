<?php

declare (strict_types=1);
namespace Rector\Configuration;

use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Skipper\File_System\Path_Normalizer;
final class Vendor_Miss_Analyse_Guard
{
    /**
     * @param string[] $filePaths
     */
    public function is_vendor_analyzed(array $file_paths): bool
    {
        if ($this->has_downgrade_sets()) {
            return \false;
        }
        return $this->contains_vendor_path($file_paths);
    }
    private function has_downgrade_sets(): bool
    {
        $registered_rector_sets = Simple_Parameter_Provider::provide_array_parameter(\Rector\Configuration\Option::REGISTERED_RECTOR_SETS);
        foreach ($registered_rector_sets as $registered_rector_set) {
            if (strpos((string) $registered_rector_set, 'downgrade-') !== \false) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param string[] $filePaths
     */
    private function contains_vendor_path(array $file_paths): bool
    {
        $cwd_length = strlen(getcwd());
        foreach ($file_paths as $file_path) {
            $normalized_path = Path_Normalizer::normalize(realpath($file_path));
            if (strncmp((string) substr($normalized_path, $cwd_length), '/vendor/', strlen('/vendor/')) === 0) {
                return \true;
            }
        }
        return \false;
    }
}