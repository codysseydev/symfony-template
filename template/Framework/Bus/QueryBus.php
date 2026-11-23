<?php

declare(strict_types=1);

namespace Framework\Bus;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends a query through `query.bus` and returns what its one handler returns.
 *
 * The return type is `mixed` here. The PHPStan extension in
 * tools/phpstan/QueryBusReturnTypeExtension.php narrows it to the return type
 * of `<Query>Handler::__invoke()`.
 */
final class QueryBus
{
    use HandleTrait;

    public function __construct(
        #[Autowire(service: 'query.bus')]
        MessageBusInterface $queryBus,
    ) {
        $this->messageBus = $queryBus;
    }

    public function ask(object $query): mixed
    {
        return $this->handle($query);
    }
}
