<?php

declare (strict_types=1);
namespace Rector\Set\Set_Provider;

use Rector\Set\Contract\Set_Interface;
use Rector\Set\Contract\Set_Provider_Interface;
use Rector\Set\Enum\Set_Group;
use Rector\Set\Value_Object\Composer_Triggered_Set;
use Rector\Set\Value_Object\Set;
final class Core_Set_Provider implements Set_Provider_Interface
{
    /**
     * @return SetInterface[]
     */
    public function provide(): array
    {
        return [new Set(Set_Group::CORE, 'Code Quality', __DIR__ . '/../../../config/set/code-quality.php'), new Set(Set_Group::CORE, 'Coding Style', __DIR__ . '/../../../config/set/coding-style.php'), new Set(Set_Group::CORE, 'Dead Code', __DIR__ . '/../../../config/set/dead-code.php'), new Set(Set_Group::CORE, 'DateTime to Carbon', __DIR__ . '/../../../config/set/datetime-to-carbon.php'), new Set(Set_Group::CORE, 'Instanceof', __DIR__ . '/../../../config/set/instanceof.php'), new Set(Set_Group::CORE, 'Early return', __DIR__ . '/../../../config/set/early-return.php'), new Set(Set_Group::CORE, 'Gmagick to Imagick', __DIR__ . '/../../../config/set/gmagick-to-imagick.php'), new Set(Set_Group::CORE, 'Naming', __DIR__ . '/../../../config/set/naming.php'), new Set(Set_Group::CORE, 'Privatization', __DIR__ . '/../../../config/set/privatization.php'), new Set(Set_Group::CORE, 'Strict Booleans', __DIR__ . '/../../../config/set/strict-booleans.php'), new Set(Set_Group::CORE, 'Type Declarations', __DIR__ . '/../../../config/set/type-declaration.php'), new Composer_Triggered_Set(Set_Group::NETTE_UTILS, 'nette/utils', '4.0', __DIR__ . '/../../../config/set/nette-utils/nette-utils4.php')];
    }
}