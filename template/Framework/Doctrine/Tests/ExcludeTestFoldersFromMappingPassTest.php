<?php

declare(strict_types=1);

namespace Framework\Doctrine\Tests;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Framework\Doctrine\ExcludeTestFoldersFromMappingPass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ExcludeTestFoldersFromMappingPassTest extends TestCase
{
    #[Test]
    public function itExcludesTestsAndMigrationsFoldersFromEveryAttributeDriver(): void
    {
        $module = sys_get_temp_dir() . '/' . uniqid('module-', true);
        mkdir($module . '/Place/Tests', 0o777, true);
        mkdir($module . '/Migrations', 0o777, true);
        mkdir($module . '/Place/Entity', 0o777, true);

        $container = new ContainerBuilder();
        $container->setParameter('driver.class', AttributeDriver::class);
        $container->setDefinition('driver', new Definition('%driver.class%', [[]]));
        $container->setDefinition('other', new Definition(\stdClass::class));

        (new ExcludeTestFoldersFromMappingPass([$module]))->process($container);

        $calls = $container->getDefinition('driver')->getMethodCalls();

        self::assertSame([['addExcludePaths', [[$module . '/Migrations', $module . '/Place/Tests']]]], $calls);
        self::assertSame([], $container->getDefinition('other')->getMethodCalls());
    }
}
