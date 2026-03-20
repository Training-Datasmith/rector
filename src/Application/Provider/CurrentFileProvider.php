<?php

declare (strict_types=1);
namespace Rector\Application\Provider;

use Rector\Value_Object\Application\File;
/**
 * @internal Avoid this services if possible, pass File value object or file path directly
 */
final class Current_File_Provider
{
    private ?File $file = null;
    public function set_file(File $file): void
    {
        $this->file = $file;
    }
    public function get_file(): ?File
    {
        return $this->file;
    }
}