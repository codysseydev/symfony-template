<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Architecture\Fixtures\ChildOfStatusException;
use Tests\Architecture\Fixtures\HttpFixtureException;
use Tests\Architecture\Fixtures\PlainException;
use Tests\Architecture\Fixtures\QueueFixtureException;
use Tests\Architecture\Fixtures\StatusException;

final class ExceptionRulesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, int}>
     */
    public static function exceptions(): iterable
    {
        yield 'no status' => [PlainException::class, 1];
        yield 'carries the attribute' => [StatusException::class, 0];
        yield 'inherits the attribute' => [ChildOfStatusException::class, 0];
        yield 'implements HttpExceptionInterface' => [HttpFixtureException::class, 0];
    }

    /**
     * @param class-string $fqcn
     */
    #[Test]
    #[DataProvider('exceptions')]
    public function itRequiresAnHttpStatusOnProductExceptions(string $fqcn, int $expected): void
    {
        $violations = new Violations();

        // The rule scopes to the product namespace, so the check runs on a
        // description that sits there but reflects the fixture class.
        $rule = ExceptionRules::rules()[0];
        $rule->check($this->inProductNamespace($fqcn), $violations);

        self::assertCount($expected, $violations);
    }

    #[Test]
    public function itLeavesRuntimeExceptionsAlone(): void
    {
        $violations = new Violations();

        ExceptionRules::rules()[0]->check($this->inProductNamespace(QueueFixtureException::class), $violations);

        self::assertCount(0, $violations);
    }

    /**
     * @param class-string $fixture
     */
    private function inProductNamespace(string $fixture): ClassDescription
    {
        // Rule and expression read getFQCN(); alias the fixture into the product namespace.
        $alias = ProductModules::namespace() . '\Orders\\' . (new \ReflectionClass($fixture))->getShortName();

        if (!class_exists($alias, false)) {
            class_alias($fixture, $alias);
        }

        return ClassDescription::getBuilder($alias, '/fake/' . $alias . '.php')->build();
    }
}
