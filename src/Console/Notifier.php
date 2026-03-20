<?php

declare (strict_types=1);
namespace Rector\Console;

use Rector\Exception\Configuration\Invalid_Configuration_Exception;
use Rector_Prefix202603\Symfony\Component\Console\Input\Argv_Input;
use Rector_Prefix202603\Symfony\Component\Console\Output\Console_Output;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class Notifier
{
    public static function notify_not_suitable_method_for_php74(string $called_method): void
    {
        if (\PHP_VERSION_ID >= 80000) {
            return;
        }
        $message = sprintf('The "%s()" method uses named arguments. Its suitable for PHP 8.0+. In lower PHP versions, use "withSets([...])" method instead', $called_method);
        $symfony_style = new Symfony_Style(new Argv_Input(), new Console_Output());
        $symfony_style->warning($message);
        sleep(3);
    }
    public static function error_with_php_sets_not_suitable_for_php74and_lower(): void
    {
        if (\PHP_VERSION_ID >= 80000) {
            return;
        }
        throw new Invalid_Configuration_Exception('The "->withPhpSets()" method uses named arguments. Its suitable for PHP 8.0+. Use more explicit "->withPhp53Sets()" ... "->withPhp74Sets()" in lower PHP versions instead.');
    }
}