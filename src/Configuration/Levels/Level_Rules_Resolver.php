<?php

declare (strict_types=1);
namespace Rector\Configuration\Levels;

use Rector\Contract\Rector\Rector_Interface;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Level_Rules_Resolver
{
    /**
     * @param array<class-string<RectorInterface>> $availableRules
     * @return array<class-string<RectorInterface>>
     */
    public static function resolve(int $level, array $available_rules, string $method_name): array
    {
        // level < 0 is not allowed
        Assert::natural($level, sprintf('Level must be >= 0 on %s', $method_name));
        Assert::all_is_a_of($available_rules, Rector_Interface::class);
        $rules_count = count($available_rules);
        if ($available_rules === []) {
            throw new Should_Not_Happen_Exception(sprintf('There are no available rules in "%s()", define the available rules first', $method_name));
        }
        // start with 0
        $max_level = $rules_count - 1;
        if ($level > $max_level) {
            $level = $max_level;
        }
        $level_rules = [];
        for ($i = 0; $i <= $level; ++$i) {
            $level_rules[] = $available_rules[$i];
        }
        return $level_rules;
    }
}