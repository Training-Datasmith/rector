<?php

declare (strict_types=1);
namespace Rector\Contract\Rector;

use Symplify\Rule_Doc_Generator\Contract\Configurable_Rule_Interface;
interface Configurable_Rector_Interface extends \Rector\Contract\Rector\Rector_Interface, Configurable_Rule_Interface
{
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void;
}