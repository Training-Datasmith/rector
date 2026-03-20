<?php

declare (strict_types=1);
namespace Rector\Caching\Value_Object;

final class Cache_File_Paths
{
    /**
     * @readonly
     */
    private string $first_directory;
    /**
     * @readonly
     */
    private string $second_directory;
    /**
     * @readonly
     */
    private string $file_path;
    public function __construct(string $first_directory, string $second_directory, string $file_path)
    {
        $this->first_directory = $first_directory;
        $this->second_directory = $second_directory;
        $this->file_path = $file_path;
    }
    public function get_first_directory(): string
    {
        return $this->first_directory;
    }
    public function get_second_directory(): string
    {
        return $this->second_directory;
    }
    public function get_file_path(): string
    {
        return $this->file_path;
    }
}