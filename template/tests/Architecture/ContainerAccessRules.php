<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Rule;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A product class gets its collaborators through its constructor. Injecting the
 * container turns every dependency into a hidden one, which is the service
 * locator pattern. Framework\ is exempt: the kernel and the buses wire things.
 */
final class ContainerAccessRules
{
    /**
     * @return list<ArchRule>
     */
    public static function rules(): array
    {
        return [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces(ProductModules::namespace()))
                ->should(new NotDependsOnTheseNamespaces([
                    PsrContainerInterface::class,
                    ContainerInterface::class,
                ]))
                ->because('product classes take their dependencies through the constructor and never the container'),
        ];
    }
}
