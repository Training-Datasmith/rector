<?php

declare (strict_types=1);
namespace Rector\Node_Name_Resolver\Regex;

final class Regex_Pattern_Detector
{
    /**
     * @var string[]
     *
     * This prevents miss matching like "aMethoda"
     */
    private const POSSIBLE_DELIMITERS = ['#', '~', '/'];
    /**
     * @var array<string, string>
     */
    private const START_AND_END_DELIMITERS = ['(' => ')', '{' => '}', '[' => ']', '<' => '>'];
    public function is_regex_pattern(string $name): bool
    {
        if (strlen($name) <= 2) {
            return \false;
        }
        $first_char = $name[0];
        $last_char = $name[strlen($name) - 1];
        if ($first_char !== $last_char) {
            foreach (self::START_AND_END_DELIMITERS as $start => $end) {
                if ($first_char !== $start) {
                    continue;
                }
                if ($last_char !== $end) {
                    continue;
                }
                return \true;
            }
            return \false;
        }
        return in_array($first_char, self::POSSIBLE_DELIMITERS, \true);
    }
}