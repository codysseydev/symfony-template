<?php

declare(strict_types=1);

namespace Framework\Bus;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends a command through `command.bus`, which wraps the handler in a Doctrine
 * transaction (middleware `doctrine_transaction`). A handler that throws has its
 * exception rethrown as it was, not wrapped in HandlerFailedException, so a
 * domain exception keeps its HTTP status.
 */
final readonly class CommandBus
{
    public function __construct(
        #[Autowire(service: 'command.bus')]
        private MessageBusInterface $commandBus,
    ) {}

    public function dispatch(object $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $handlerFailedException) {
            throw array_values($handlerFailedException->getWrappedExceptions())[0] ?? $handlerFailedException;
        }
    }
}
