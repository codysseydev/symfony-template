<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetStatusQueryHandler
{
    public function __construct(
        private Connection $database,
        #[Autowire(env: 'APP_NAME')]
        private string $app,
        #[Autowire('%kernel.environment%')]
        private string $environment,
    ) {}

    public function __invoke(GetStatusQuery $query): StatusReport
    {
        return new StatusReport(
            $this->app,
            $this->environment,
            $this->databaseIsReachable(),
        );
    }

    private function databaseIsReachable(): bool
    {
        try {
            $this->database->executeQuery('select 1');
        } catch (DbalException) {
            return false;
        }

        return true;
    }
}
