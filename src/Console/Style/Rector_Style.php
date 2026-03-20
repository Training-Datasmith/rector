<?php

declare (strict_types=1);
namespace Rector\Console\Style;

use Override;
use Rector_Prefix202603\Ondra_M\Ci_Detector\Ci_Detector;
use Rector_Prefix202603\Symfony\Component\Console\Exception\RuntimeException;
use Rector_Prefix202603\Symfony\Component\Console\Helper\Progress_Bar;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class Rector_Style extends Symfony_Style
{
    private ?Progress_Bar $progress_bar = null;
    private ?bool $is_ci_detected = null;
    public function __construct(Input_Interface $input, Output_Interface $output)
    {
        parent::__construct($input, $output);
        // silent output in tests
        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            $this->set_verbosity(Output_Interface::VERBOSITY_QUIET);
        }
    }
    /**
     * @see https://github.com/phpstan/phpstan-src/commit/0993d180e5a15a17631d525909356081be59ffeb
     */
    #[Override]
    public function create_progress_bar(int $max = 0): Progress_Bar
    {
        $progress_bar = parent::create_progress_bar($max);
        $progress_bar->set_overwrite(!$this->is_ci_detected());
        $is_ci_detected = $this->is_ci_detected();
        $progress_bar->set_overwrite(!$is_ci_detected);
        if ($is_ci_detected) {
            $progress_bar->min_seconds_between_redraws(15);
            $progress_bar->max_seconds_between_redraws(30);
        } elseif (\DIRECTORY_SEPARATOR === '\\') {
            // windows
            $progress_bar->min_seconds_between_redraws(0.5);
            $progress_bar->max_seconds_between_redraws(2);
        } else {
            // *nix
            $progress_bar->min_seconds_between_redraws(0.1);
            $progress_bar->max_seconds_between_redraws(0.5);
        }
        $this->progress_bar = $progress_bar;
        return $progress_bar;
    }
    #[Override]
    public function progress_advance(int $step = 1): void
    {
        // hide progress bar in tests
        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            return;
        }
        $progress_bar = $this->get_progress_bar();
        $progress_bar->advance($step);
    }
    private function is_ci_detected(): bool
    {
        if ($this->is_ci_detected === null) {
            $ci_detector = new Ci_Detector();
            $this->is_ci_detected = $ci_detector->is_ci_detected();
        }
        return $this->is_ci_detected;
    }
    private function get_progress_bar(): Progress_Bar
    {
        if (!isset($this->progress_bar)) {
            throw new RuntimeException('The ProgressBar is not started.');
        }
        return $this->progress_bar;
    }
}