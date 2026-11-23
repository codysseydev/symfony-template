<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

$product = require __DIR__ . '/../modules.php';

return static function (ContainerConfigurator $container) use ($product): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('Framework\\', __DIR__ . '/../Framework/')
        ->exclude([
            __DIR__ . '/../Framework/Kernel.php',
            __DIR__ . '/../Framework/Tests',
            __DIR__ . '/../Framework/**/Tests',
        ]);

    // Each module registers its own services in <module>-services.php.
    foreach ($product['modules'] as $module) {
        $container->import(
            __DIR__ . '/../' . $product['namespace'] . '/' . $module . '/' . strtolower($module) . '-services.php',
        );
    }
};
