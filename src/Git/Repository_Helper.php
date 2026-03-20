<?php

declare (strict_types=1);
namespace Rector\Git;

use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symfony\Component\Process\Process;
final class Repository_Helper
{
    /**
     * @see https://regex101.com/r/etcmog/2
     * @var string
     */
    private const GITHUB_REPOSITORY_REGEX = '#github\.com[:\/](?<repository_name>.*?)\.git#';
    public static function resolve_github_repository_name(string $current_directory): ?string
    {
        // resolve current repository name
        $process = new Process(['git', 'remote', 'get-url', 'origin'], $current_directory, null, null, null);
        $process->run();
        $output = $process->get_output();
        $match = Strings::match($output, self::GITHUB_REPOSITORY_REGEX);
        return $match['repository_name'] ?? null;
    }
}