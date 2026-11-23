<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus;

use Framework\Bus\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetStatusController
{
    public function __construct(private QueryBus $queryBus) {}

    #[Route('/api/health/status', name: 'health.status', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $report = $this->queryBus->ask(new GetStatusQuery());

        return new JsonResponse(['data' => (new StatusResource($report))->toArray()]);
    }
}
