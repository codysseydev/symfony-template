<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

$product = require dirname(__DIR__, 2) . '/modules.php';

return static function (ContainerConfigurator $container) use ($product): void {
    // Each module owns a Migrations/ folder and a migration namespace.
    $paths = [];

    foreach ($product['modules'] as $module) {
        $paths[$product['namespace'] . '\\' . $module . '\\Migrations'] = '%kernel.project_dir%/' . $product['namespace'] . '/' . $module . '/Migrations';
    }

    $container->extension('doctrine_migrations', [
        'migrations_paths' => $paths,
        'enable_profiler' => false,
    ]);
};
