<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

$product = require __DIR__ . '/../modules.php';

return static function (RoutingConfigurator $routes) use ($product): void {
    // Each module registers its own routes in <module>-routes.php.
    foreach ($product['modules'] as $module) {
        $routes->import(
            __DIR__ . '/../' . $product['namespace'] . '/' . $module . '/' . strtolower($module) . '-routes.php',
        );
    }
};
