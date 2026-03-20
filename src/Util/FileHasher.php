<?php

declare (strict_types=1);
namespace Rector\Util;

use Rector\Exception\Should_Not_Happen_Exception;
/**
 * @see \Rector\Tests\Util\FileHasherTest
 */
final class File_Hasher
{
    /**
     * cryptographic insecure hashing of a string
     */
    public function hash(string $string): string
    {
        return hash($this->get_algo(), $string);
    }
    /**
     * cryptographic insecure hashing of files
     *
     * @param string[] $files
     */
    public function hash_files(array $files): string
    {
        $config_hash = '';
        $algo = $this->get_algo();
        foreach ($files as $file) {
            $hash = hash_file($algo, $file);
            if ($hash === \false) {
                throw new Should_Not_Happen_Exception(sprintf('File %s is not readable', $file));
            }
            $config_hash .= $hash;
        }
        return $config_hash;
    }
    private function get_algo(): string
    {
        //see https://php.watch/articles/php-hash-benchmark
        if (\PHP_VERSION_ID >= 80100) {
            // if xxh128 is available use it, as it is way faster
            return 'xxh128';
        }
        return 'md4';
    }
}