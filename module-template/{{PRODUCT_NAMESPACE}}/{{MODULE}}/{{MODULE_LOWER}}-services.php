<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    // Read by tests/Architecture/ModuleListTest to prove the kernel loaded this file.
    $container->parameters()->set('module.{{MODULE_LOWER}}', true);

    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->load('{{PRODUCT_NAMESPACE}}\{{MODULE}}\\', __DIR__ . '/')
        ->exclude([
            __DIR__ . '/Migrations',
            __DIR__ . '/Tests',
            __DIR__ . '/**/Tests',
        ]);
};
