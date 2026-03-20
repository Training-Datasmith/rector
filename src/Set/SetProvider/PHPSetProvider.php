<?php

declare (strict_types=1);
namespace Rector\Set\Set_Provider;

use Rector\Set\Contract\Set_Interface;
use Rector\Set\Contract\Set_Provider_Interface;
use Rector\Set\Enum\Set_Group;
use Rector\Set\Value_Object\Set;
final class Php_Set_Provider implements Set_Provider_Interface
{
    /**
     * @return SetInterface[]
     */
    public function provide(): array
    {
        return [new Set(Set_Group::PHP, 'PHP 5.3', __DIR__ . '/../../../config/set/php53.php'), new Set(Set_Group::PHP, 'PHP 5.4', __DIR__ . '/../../../config/set/php54.php'), new Set(Set_Group::PHP, 'PHP 5.5', __DIR__ . '/../../../config/set/php55.php'), new Set(Set_Group::PHP, 'PHP 5.6', __DIR__ . '/../../../config/set/php56.php'), new Set(Set_Group::PHP, 'PHP 7.0', __DIR__ . '/../../../config/set/php70.php'), new Set(Set_Group::PHP, 'PHP 7.1', __DIR__ . '/../../../config/set/php71.php'), new Set(Set_Group::PHP, 'PHP 7.2', __DIR__ . '/../../../config/set/php72.php'), new Set(Set_Group::PHP, 'PHP 7.3', __DIR__ . '/../../../config/set/php73.php'), new Set(Set_Group::PHP, 'PHP 7.4', __DIR__ . '/../../../config/set/php74.php'), new Set(Set_Group::PHP, 'PHP 8.0', __DIR__ . '/../../../config/set/php80.php'), new Set(Set_Group::PHP, 'PHP 8.1', __DIR__ . '/../../../config/set/php81.php'), new Set(Set_Group::PHP, 'PHP 8.2', __DIR__ . '/../../../config/set/php82.php'), new Set(Set_Group::PHP, 'PHP 8.3', __DIR__ . '/../../../config/set/php83.php'), new Set(Set_Group::PHP, 'PHP 8.4', __DIR__ . '/../../../config/set/php84.php'), new Set(Set_Group::PHP, 'PHP 8.5', __DIR__ . '/../../../config/set/php85.php'), new Set(Set_Group::PHP, 'Polyfills', __DIR__ . '/../../../config/set/php-polyfills.php')];
    }
}