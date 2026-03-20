<?php

declare (strict_types=1);
namespace Rector\Console;

use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
/**
 * @api
 */
final class Exit_Code
{
    /**
     * @var int
     */
    public const SUCCESS = Command::SUCCESS;
    /**
     * @var int
     */
    public const FAILURE = Command::FAILURE;
    /**
     * @var int
     */
    public const CHANGED_CODE = 2;
}