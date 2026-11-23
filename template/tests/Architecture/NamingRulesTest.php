<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;

final class NamingRulesTest extends TestCase
{
    #[Test]
    public function itReportsAnAsCommandClassWithoutTheConsoleCommandSuffix(): void
    {
        $violations = new Violations();

        NamingRules::rules()[0]->check(
            ClassDescription::getBuilder('Fake\ImportCommand', '/fake/ImportCommand.php')
                ->addAttribute(AsCommand::class, 8)
                ->build(),
            $violations,
        );

        self::assertCount(1, $violations);
    }

    #[Test]
    public function itAcceptsAnAsCommandClassNamedConsoleCommand(): void
    {
        $violations = new Violations();

        NamingRules::rules()[0]->check(
            ClassDescription::getBuilder('Fake\ImportConsoleCommand', '/fake/ImportConsoleCommand.php')
                ->addAttribute(AsCommand::class, 8)
                ->build(),
            $violations,
        );

        self::assertCount(0, $violations);
    }

    #[Test]
    public function itLeavesClassesWithoutTheAttributeAlone(): void
    {
        $violations = new Violations();

        NamingRules::rules()[0]->check(
            ClassDescription::getBuilder('Fake\PlaceOrderCommand', '/fake/PlaceOrderCommand.php')->build(),
            $violations,
        );

        self::assertCount(0, $violations);
    }
}
