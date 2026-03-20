<?php

declare (strict_types=1);
namespace Rector\Set\Contract;

interface Set_Interface
{
    public function get_group_name(): string;
    public function get_name(): string;
    public function get_set_file_path(): string;
}