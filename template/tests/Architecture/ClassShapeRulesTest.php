<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Rules\Violations;
use Doctrine\ORM\Mapping\Entity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClassShapeRulesTest extends TestCase
{
    /**
     * @return iterable<string, array{ClassDescriptionBuilder, int}>
     */
    public static function shapes(): iterable
    {
        $product = ProductModules::namespace();

        yield 'non-final controller' => [self::describe('Fake\FakeController')->setFinal(false), 1];
        yield 'final controller' => [self::describe('Fake\FakeController')->setFinal(true), 0];
        yield 'non-final handler' => [self::describe('Fake\FakeHandler')->setFinal(false)->setReadonly(true), 1];
        yield 'final handler that is not readonly' => [self::describe('Fake\FakeHandler')->setFinal(true)->setReadonly(false), 1];
        yield 'final readonly handler' => [self::describe('Fake\FakeHandler')->setFinal(true)->setReadonly(true), 0];
        yield 'domain command that is not readonly' => [self::describe('Fake\PlaceOrderCommand')->setFinal(true)->setReadonly(false), 1];
        yield 'final readonly domain command' => [self::describe('Fake\PlaceOrderCommand')->setFinal(true)->setReadonly(true), 0];
        yield 'console command is left alone' => [self::describe('Fake\ImportConsoleCommand')->setFinal(false)->setReadonly(false), 0];
        yield 'query that is not readonly' => [self::describe('Fake\ListOrdersQuery')->setFinal(true)->setReadonly(false), 1];
        yield 'final readonly query' => [self::describe('Fake\ListOrdersQuery')->setFinal(true)->setReadonly(true), 0];
        yield 'non-final event in the product namespace' => [self::describe($product . '\Orders\OrderPlacedEvent')->setFinal(false)->setReadonly(true), 1];
        yield 'event in the product namespace that is not readonly' => [self::describe($product . '\Orders\OrderPlacedEvent')->setFinal(true)->setReadonly(false), 1];
        yield 'final readonly event' => [self::describe($product . '\Orders\OrderPlacedEvent')->setFinal(true)->setReadonly(true), 0];
        yield 'entity named like an event' => [self::describe($product . '\Orders\ReceivedEvent')->setFinal(true)->setReadonly(false)->addAttribute(Entity::class, 10), 0];
        yield 'event outside the product namespace' => [self::describe('Vendor\SomethingEvent')->setFinal(false)->setReadonly(false), 0];
    }

    #[Test]
    #[DataProvider('shapes')]
    public function itCountsTheViolationsOfEachShape(ClassDescriptionBuilder $builder, int $expected): void
    {
        $violations = new Violations();
        $class = $builder->build();

        foreach (ClassShapeRules::rules() as $rule) {
            $rule->check($class, $violations);
        }

        self::assertCount($expected, $violations);
    }

    private static function describe(string $fqcn): ClassDescriptionBuilder
    {
        return ClassDescription::getBuilder($fqcn, '/fake/' . str_replace('\\', '/', $fqcn) . '.php');
    }
}
