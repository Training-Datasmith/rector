<?php

declare (strict_types=1);
namespace Rector\Skipper\Skip_Criteria_Resolver;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\File_System\File_Path_Helper;
use Rector\Testing\Php_Unit\Static_Php_Unit_Environment;
/**
 * @see \Rector\Tests\Skipper\SkipCriteriaResolver\SkippedPathsResolver\SkippedPathsResolverTest
 */
final class Skipped_Paths_Resolver
{
    /**
     * @readonly
     */
    private File_Path_Helper $file_path_helper;
    /**
     * @var null|string[]
     */
    private ?array $skipped_paths = null;
    public function __construct(File_Path_Helper $file_path_helper)
    {
        $this->file_path_helper = $file_path_helper;
    }
    /**
     * @return string[]
     */
    public function resolve(): array
    {
        // disable cache in tests
        if (Static_Php_Unit_Environment::is_php_unit_run()) {
            $this->skipped_paths = null;
        }
        // already cached, even only empty array
        if ($this->skipped_paths !== null) {
            return $this->skipped_paths;
        }
        $skip = Simple_Parameter_Provider::provide_array_parameter(Option::SKIP);
        $this->skipped_paths = [];
        foreach ($skip as $key => $value) {
            if (!is_int($key)) {
                continue;
            }
            if (strpos((string) $value, '*') !== \false) {
                $this->skipped_paths[] = $this->file_path_helper->normalize_path_and_schema($value);
                continue;
            }
            if (file_exists($value)) {
                $this->skipped_paths[] = $this->file_path_helper->normalize_path_and_schema($value);
            }
        }
        return $this->skipped_paths;
    }
}