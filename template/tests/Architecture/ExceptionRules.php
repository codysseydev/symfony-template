<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsNotA;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Rule;

/**
 * A domain exception answers a request. One with no HTTP status falls through
 * to Symfony's error handler, which answers an arbitrary Throwable with a raw
 * 500. RuntimeException descendants are excluded: they mark a queue or
 * scheduler invariant that never answers a request and should be reported.
 */
final class ExceptionRules
{
    /**
     * @return list<ArchRule>
     */
    public static function rules(): array
    {
        return [
            Rule::allClasses()
                ->that(new HaveNameMatching('*Exception'))
                ->andThat(new ResideInOneOfTheseNamespaces(ProductModules::namespace()))
                ->andThat(new IsNotA(\RuntimeException::class))
                ->should(new HasHttpStatus())
                ->because('a domain exception with no HTTP status surfaces as a raw 500 instead of a clean error response'),
        ];
    }
}
