<?php

declare(strict_types=1);

namespace Framework\Bus\Tests;

use Framework\Bus\CommandBus;
use Framework\Bus\QueryBus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class BusTest extends TestCase
{
    #[Test]
    public function theQueryBusReturnsWhatTheHandlerReturns(): void
    {
        $bus = new QueryBus(new MessageBus([new HandleMessageMiddleware(new HandlersLocator([
            \stdClass::class => [static fn(\stdClass $query): string => 'answer'],
        ]))]));

        self::assertSame('answer', $bus->ask(new \stdClass()));
    }

    #[Test]
    public function theCommandBusRunsTheHandler(): void
    {
        $ran = false;
        $bus = new CommandBus(new MessageBus([new HandleMessageMiddleware(new HandlersLocator([
            \stdClass::class => [static function (\stdClass $command) use (&$ran): void {
                $ran = true;
            }],
        ]))]));

        $bus->dispatch(new \stdClass());

        self::assertTrue($ran);
    }

    #[Test]
    public function theCommandBusRethrowsTheHandlerExceptionUnwrapped(): void
    {
        $bus = new CommandBus(new MessageBus([new HandleMessageMiddleware(new HandlersLocator([
            \stdClass::class => [static function (\stdClass $command): never {
                throw new \DomainException('rejected');
            }],
        ]))]));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('rejected');

        $bus->dispatch(new \stdClass());
    }
}
