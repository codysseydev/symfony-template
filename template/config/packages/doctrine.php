<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

$product = require dirname(__DIR__, 2) . '/modules.php';

return static function (ContainerConfigurator $container) use ($product): void {
    // Every module is mapped from its root, so an entity can sit in the slice
    // that owns it. Framework\Kernel adds a compiler pass that keeps the
    // Tests/ and Migrations/ folders out of the scan.
    $mappings = [];

    foreach ($product['modules'] as $module) {
        $mappings[$module] = [
            'type' => 'attribute',
            'is_bundle' => false,
            'dir' => dirname(__DIR__, 2) . '/' . $product['namespace'] . '/' . $module,
            'prefix' => $product['namespace'] . '\\' . $module,
            'alias' => $module,
        ];
    }

    $container->extension('doctrine', [
        'dbal' => [
            'url' => '%env(resolve:DATABASE_URL)%',
        ],
        'orm' => [
            'enable_native_lazy_objects' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping' => false,
            'mappings' => $mappings,
        ],
    ]);
};
