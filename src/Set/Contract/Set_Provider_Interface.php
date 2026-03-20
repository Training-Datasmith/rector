<?php

declare (strict_types=1);
namespace Rector\Set\Contract;

interface Set_Provider_Interface
{
    /**
     * @return SetInterface[]
     */
    public function provide(): array;
}