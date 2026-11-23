<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ContainerAccessRulesTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function containers(): iterable
    {
        yield 'PSR container' => [PsrContainerInterface::class];
        yield 'Symfony container' => [ContainerInterface::class];
    }

    #[Test]
    #[DataProvider('containers')]
    public function itReportsAProductClassThatDependsOnAContainer(string $container): void
    {
        $violations = new Violations();

        ContainerAccessRules::rules()[0]->check(
            $this->classDependingOn(ProductModules::namespace() . '\Orders\PlaceOrder\Fake', $container),
            $violations,
        );

        self::assertCount(1, $violations);
    }

    #[Test]
    public function itAcceptsAProductClassWithOrdinaryDependencies(): void
    {
        $violations = new Violations();

        ContainerAccessRules::rules()[0]->check(
            $this->classDependingOn(ProductModules::namespace() . '\Orders\PlaceOrder\Fake', \Psr\Log\LoggerInterface::class),
            $violations,
        );

        self::assertCount(0, $violations);
    }

    #[Test]
    public function itExemptsFramework(): void
    {
        $violations = new Violations();

        ContainerAccessRules::rules()[0]->check(
            $this->classDependingOn('Framework\Fake', ContainerInterface::class),
            $violations,
        );

        self::assertCount(0, $violations);
    }

    private function classDependingOn(string $fqcn, string $dependencyFqcn): ClassDescription
    {
        return ClassDescription::getBuilder($fqcn, '/fake/' . str_replace('\\', '/', $fqcn) . '.php')
            ->addDependency(new ClassDependency($dependencyFqcn, 10))
            ->build();
    }
}
