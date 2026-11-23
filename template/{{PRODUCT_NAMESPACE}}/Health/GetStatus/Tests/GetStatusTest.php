<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus\Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetStatusTest extends WebTestCase
{
    #[Test]
    public function itReportsTheAppAndAReachableDatabase(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/health/status');

        self::assertResponseIsSuccessful();

        $body = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($body);
        self::assertSame(
            ['app' => '{{PRODUCT_NAME}}', 'environment' => 'test', 'database_reachable' => true],
            $body['data'] ?? null,
        );
    }
}
