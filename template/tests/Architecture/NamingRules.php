<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Expression\ForClasses\HaveAttribute;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Rule;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Console commands carry #[AsCommand] and end in `ConsoleCommand`. The suffix
 * keeps them apart from the domain `*Command` classes, which the class shape
 * rules hold to final readonly.
 */
final class NamingRules
{
    /**
     * @return list<ArchRule>
     */
    public static function rules(): array
    {
        return [
            Rule::allClasses()
                ->that(new HaveAttribute(AsCommand::class))
                ->should(new HaveNameMatching('*ConsoleCommand'))
                ->because('console commands must carry the ConsoleCommand suffix'),
        ];
    }
}
