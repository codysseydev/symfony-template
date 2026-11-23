<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Rule;

/**
 * Modules are isolated bounded contexts: a class in one module must not depend
 * on a sibling module's namespace. Cross-module integration goes through domain
 * events and explicit contracts, never direct imports.
 *
 * The module list comes from modules.php. To allow a shared kernel module, name
 * it in SHARED_KERNELS: every module may then depend on it.
 */
final class ModuleIsolationRules
{
    /**
     * @var list<string>
     */
    private const array SHARED_KERNELS = [];

    /**
     * @return list<ArchRule>
     */
    public static function rules(): array
    {
        $namespace = ProductModules::namespace();
        $modules = ProductModules::names();
        $rules = [];

        foreach ($modules as $module) {
            $forbidden = array_values(array_map(
                static fn(string $sibling): string => $namespace . '\\' . $sibling,
                array_filter(
                    $modules,
                    static fn(string $candidate): bool => $candidate !== $module
                        && ! in_array($candidate, self::SHARED_KERNELS, true),
                ),
            ));

            if ([] === $forbidden) {
                continue;
            }

            $rules[] = Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces($namespace . '\\' . $module))
                ->should(new NotDependsOnTheseNamespaces($forbidden))
                ->because($module . ' is an isolated bounded context and must not depend on sibling modules');
        }

        return $rules;
    }
}
