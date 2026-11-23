<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ModuleIsolationRulesTest extends TestCase
{
    #[Test]
    public function itReportsAModuleClassDependingOnASiblingModule(): void
    {
        $namespace = ProductModules::namespace();
        $rules = ModuleIsolationRules::rules();

        if (count(ProductModules::names()) < 2) {
            self::assertSame([], $rules);

            return;
        }

        $violations = new Violations();
        $rules[0]->check(
            $this->classDependingOn(
                $namespace . '\\' . ProductModules::names()[0] . '\FakeClass',
                $namespace . '\\' . ProductModules::names()[1] . '\OtherFakeClass',
            ),
            $violations,
        );

        self::assertCount(1, $violations);
    }

    #[Test]
    public function itReportsAFrameworkClassDependingOnAProductModule(): void
    {
        $violations = new Violations();
        FrameworkIsolationRules::rules()[0]->check(
            $this->classDependingOn('Framework\FakeClass', ProductModules::namespace() . '\Health\Fake'),
            $violations,
        );

        self::assertCount(1, $violations);
    }

    private function classDependingOn(string $fqcn, string $dependencyFqcn): ClassDescription
    {
        return ClassDescription::getBuilder($fqcn, '/fake/' . str_replace('\\', '/', $fqcn) . '.php')
            ->setFinal(true)
            ->addDependency(new ClassDependency($dependencyFqcn, 10))
            ->build();
    }
}
