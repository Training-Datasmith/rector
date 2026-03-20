<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Php_Stan\Scope;

use Php_Stan\Analyser\Mutating_Scope;
use Php_Stan\Analyser\Scope_Context;
use Php_Stan\Analyser\Scope_Factory as PHPStanScopeFactory;
final class Scope_Factory
{
    /**
     * @readonly
     */
    private Php_Stan_Scope_Factory $php_stan_scope_factory;
    public function __construct(Php_Stan_Scope_Factory $php_stan_scope_factory)
    {
        $this->php_stan_scope_factory = $php_stan_scope_factory;
    }
    public function create_from_file(string $file_path): Mutating_Scope
    {
        $scope_context = Scope_Context::create($file_path);
        return $this->php_stan_scope_factory->create($scope_context);
    }
}