<?php

declare (strict_types=1);
namespace Rector\Skipper\Skipper;

use Php_Parser\Node;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Process_Analyzer\Rectified_Analyzer;
use Rector\Skipper\Skip_Voter\Class_Skip_Voter;
/**
 * @api
 * @see \Rector\Tests\Skipper\Skipper\SkipperTest
 */
final class Skipper
{
    /**
     * @readonly
     */
    private Rectified_Analyzer $rectified_analyzer;
    /**
     * @readonly
     */
    private \Rector\Skipper\Skipper\Path_Skipper $path_skipper;
    /**
     * @readonly
     */
    private Class_Skip_Voter $class_skip_voter;
    public function __construct(Rectified_Analyzer $rectified_analyzer, \Rector\Skipper\Skipper\Path_Skipper $path_skipper, Class_Skip_Voter $class_skip_voter)
    {
        $this->rectified_analyzer = $rectified_analyzer;
        $this->path_skipper = $path_skipper;
        $this->class_skip_voter = $class_skip_voter;
    }
    /**
     * @param string|object $element
     */
    public function should_skip_element($element): bool
    {
        return $this->should_skip_element_and_file_path($element, __FILE__);
    }
    public function should_skip_file_path(string $file_path): bool
    {
        return $this->path_skipper->should_skip($file_path);
    }
    /**
     * @param string|object $element
     */
    public function should_skip_element_and_file_path($element, string $file_path): bool
    {
        if (!$this->class_skip_voter->match($element)) {
            return \false;
        }
        return $this->class_skip_voter->should_skip($element, $file_path);
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     * @param string|object $element
     */
    public function should_skip_current_node($element, string $file_path, string $rector_class, Node $node): bool
    {
        if ($this->should_skip_element_and_file_path($element, $file_path)) {
            return \true;
        }
        return $this->rectified_analyzer->has_rectified($rector_class, $node);
    }
}