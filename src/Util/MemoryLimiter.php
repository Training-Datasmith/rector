<?php

declare (strict_types=1);
namespace Rector\Util;

use Rector\Exception\Configuration\Invalid_Configuration_Exception;
use Rector\Value_Object\Configuration;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @inspiration https://github.com/phpstan/phpstan-src/commit/ccc046ca473dcdb5ce9225cc05d7808f2e327f40
 */
final class Memory_Limiter
{
    /**
     * @see https://regex101.com/r/pmiGUM/1
     * @var string
     */
    private const VALID_MEMORY_LIMIT_REGEX = '#^-?\d+[kMG]?$#i';
    public function adjust(Configuration $configuration): void
    {
        $memory_limit = $configuration->get_memory_limit();
        if ($memory_limit === null) {
            return;
        }
        $this->validate_memory_limit_format($memory_limit);
        $memory_set_result = ini_set('memory_limit', $memory_limit);
        if ($memory_set_result === \false) {
            $error_message = sprintf('Memory limit "%s" cannot be set.', $memory_limit);
            throw new Invalid_Configuration_Exception($error_message);
        }
    }
    private function validate_memory_limit_format(string $memory_limit): void
    {
        $memory_limit_format_match = Strings::match($memory_limit, self::VALID_MEMORY_LIMIT_REGEX);
        if ($memory_limit_format_match !== null) {
            return;
        }
        $error_message = sprintf('Invalid memory limit format "%s".', $memory_limit);
        throw new Invalid_Configuration_Exception($error_message);
    }
}