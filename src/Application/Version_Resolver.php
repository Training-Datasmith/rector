<?php

declare (strict_types=1);
namespace Rector\Application;

use DateTime;
use Rector\Exception\Version_Exception;
/**
 * @api
 *
 * Inspired by https://github.com/composer/composer/blob/master/src/Composer/Composer.php
 * See https://github.com/composer/composer/blob/6587715d0f8cae0cd39073b3bc5f018d0e6b84fe/src/Composer/Compiler.php#L208
 *
 * @see \Rector\Tests\Application\VersionResolverTest
 */
final class Version_Resolver
{
    /**
     * @api
     * @var string
     */
    public const PACKAGE_VERSION = '3a3942b2796f878704949f6adbbc7c23db1186d4';
    /**
     * @api
     * @var string
     */
    public const RELEASE_DATE = '2026-03-17 17:46:42';
    /**
     * @var int
     */
    private const SUCCESS_CODE = 0;
    public static function resolve_package_version(): string
    {
        // resolve current tag
        exec('git tag --points-at', $tag_exec_output, $tag_exec_result_code);
        if ($tag_exec_result_code !== self::SUCCESS_CODE) {
            throw new Version_Exception('Ensure to run compile from composer git repository clone and that git binary is available.');
        }
        if ($tag_exec_output !== []) {
            $tag = $tag_exec_output[0];
            if ($tag !== '') {
                return $tag;
            }
        }
        exec('git log --pretty="%H" -n1 HEAD', $commit_hash_exec_output, $commit_hash_result_code);
        if ($commit_hash_result_code !== 0) {
            throw new Version_Exception('Ensure to run compile from composer git repository clone and that git binary is available.');
        }
        $version = trim($commit_hash_exec_output[0]);
        return trim($version, '"');
    }
    public static function resolver_release_date_time(): DateTime
    {
        exec('git log -n1 --pretty=%ci HEAD', $output, $result_code);
        if ($result_code !== self::SUCCESS_CODE) {
            throw new Version_Exception('You must ensure to run compile from composer git repository clone and that git binary is available.');
        }
        return new DateTime(trim($output[0]));
    }
}