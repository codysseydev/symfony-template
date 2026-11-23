<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Rule;

/**
 * Framework\ is shared infrastructure that every module may use. It must not
 * depend on a product module, or the dependency arrow would close a cycle.
 */
final class FrameworkIsolationRules
{
    /**
     * @return list<ArchRule>
     */
    public static function rules(): array
    {
        return [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('Framework'))
                ->should(new NotDependsOnTheseNamespaces([ProductModules::namespace()]))
                ->because('Framework is shared infrastructure and must not depend on a product module'),
        ];
    }
}
