<?php

declare (strict_types=1);
namespace Rector\Set\Value_Object;

use Rector\Set\Contract\Set_Interface;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @api used by extensions
 */
final class Set implements Set_Interface
{
    /**
     * @readonly
     */
    private string $group_name;
    /**
     * @readonly
     */
    private string $set_name;
    /**
     * @readonly
     */
    private string $set_file_path;
    public function __construct(string $group_name, string $set_name, string $set_file_path)
    {
        $this->group_name = $group_name;
        $this->set_name = $set_name;
        $this->set_file_path = $set_file_path;
        Assert::file_exists($set_file_path);
    }
    public function get_group_name(): string
    {
        return $this->group_name;
    }
    public function get_name(): string
    {
        return $this->set_name;
    }
    public function get_set_file_path(): string
    {
        return $this->set_file_path;
    }
}