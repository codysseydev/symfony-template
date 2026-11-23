<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\{{MODULE}}\Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class {{MODULE}}ModuleTest extends KernelTestCase
{
    #[Test]
    public function itIsRegisteredThroughTheModuleList(): void
    {
        self::bootKernel();

        self::assertTrue(self::getContainer()->getParameter('module.{{MODULE_LOWER}}'));
    }
}
