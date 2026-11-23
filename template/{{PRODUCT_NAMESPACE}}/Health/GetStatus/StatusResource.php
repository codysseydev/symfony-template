<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus;

final readonly class StatusResource
{
    public function __construct(private StatusReport $report) {}

    /**
     * @return array{app: string, environment: string, database_reachable: bool}
     */
    public function toArray(): array
    {
        return [
            'app' => $this->report->app,
            'environment' => $this->report->environment,
            'database_reachable' => $this->report->databaseReachable,
        ];
    }
}
