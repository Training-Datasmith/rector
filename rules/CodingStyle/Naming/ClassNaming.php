<?php

declare (strict_types=1);

namespace Rector\CodingStyle\Naming;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use RectorPrefix202603\Nette\Utils\Strings;

final class ClassNaming
{
    /**
     * @param string|\PhpParser\Node\Name|\PhpParser\Node\Identifier|\PhpParser\Node\Stmt\ClassLike $name
     */
    public function getShortName($name): string
    {
        if ($name instanceof ClassLike) {
            if (!$name->name instanceof Identifier) {
                return '';
            }
            return $this->getShortName($name->name);
        }
        if ($name instanceof Name || $name instanceof Identifier) {
            $name = $name->toString();
        }
        $name = trim($name, '\\');
        $shortName = Strings::after($name, '\\', -1);
        if (is_string($shortName)) {
            return $shortName;
        }
        return $name;
    }
}
