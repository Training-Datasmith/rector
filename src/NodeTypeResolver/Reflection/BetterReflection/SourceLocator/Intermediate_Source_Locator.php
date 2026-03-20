<?php

declare (strict_types=1);
namespace Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator;

use Php_Stan\Better_Reflection\Identifier\Identifier;
use Php_Stan\Better_Reflection\Identifier\Identifier_Type;
use Php_Stan\Better_Reflection\Reflection\Reflection;
use Php_Stan\Better_Reflection\Reflector\Reflector;
use Php_Stan\Better_Reflection\Source_Locator\Type\Source_Locator;
use Php_Stan\File\Could_Not_Read_File_Exception;
use Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator_Provider\Dynamic_Source_Locator_Provider;
final class Intermediate_Source_Locator implements Source_Locator
{
    /**
     * @readonly
     */
    private Dynamic_Source_Locator_Provider $dynamic_source_locator_provider;
    public function __construct(Dynamic_Source_Locator_Provider $dynamic_source_locator_provider)
    {
        $this->dynamic_source_locator_provider = $dynamic_source_locator_provider;
    }
    public function locate_identifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        $source_locator = $this->dynamic_source_locator_provider->provide();
        try {
            $reflection = $source_locator->locate_identifier($reflector, $identifier);
        } catch (Could_Not_Read_File_Exception $exception) {
            return null;
        }
        if ($reflection instanceof Reflection) {
            return $reflection;
        }
        return null;
    }
    /**
     * Find all identifiers of a type
     * @return array<int, Reflection>
     */
    public function locate_identifiers_by_type(Reflector $reflector, Identifier_Type $identifier_type): array
    {
        $source_locator = $this->dynamic_source_locator_provider->provide();
        try {
            $reflections = $source_locator->locate_identifiers_by_type($reflector, $identifier_type);
        } catch (Could_Not_Read_File_Exception $exception) {
            return [];
        }
        if ($reflections !== []) {
            return $reflections;
        }
        return [];
    }
}