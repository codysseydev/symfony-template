<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus;

final readonly class StatusReport
{
    public function __construct(
        public string $app,
        public string $environment,
        public bool $databaseReachable,
    ) {}
}
