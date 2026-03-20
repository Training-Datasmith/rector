<?php

declare (strict_types=1);
namespace Rector\Testing\Contract;

interface Rector_Test_Interface
{
    public function provide_config_file_path(): string;
}