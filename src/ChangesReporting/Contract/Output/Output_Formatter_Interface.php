<?php

declare (strict_types=1);
namespace Rector\Changes_Reporting\Contract\Output;

use Rector\Value_Object\Configuration;
use Rector\Value_Object\Process_Result;
interface Output_Formatter_Interface
{
    public function get_name(): string;
    public function report(Process_Result $process_result, Configuration $configuration): void;
}