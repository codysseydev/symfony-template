<?php

declare(strict_types=1);

namespace Framework\Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class KernelTest extends KernelTestCase
{
    #[Test]
    public function itBootsWithACommandBusAndAQueryBus(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertInstanceOf(MessageBusInterface::class, $container->get('command.bus'));
        self::assertInstanceOf(MessageBusInterface::class, $container->get('query.bus'));
    }
}
