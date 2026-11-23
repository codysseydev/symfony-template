<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    // Routes are #[Route] attributes on the controllers in the slice folders.
    $routes->import(__DIR__, 'attribute', false, [
        // A glob never fails on a folder that does not exist, a plain path does.
        __DIR__ . '/{Migrations,Tests}',
        __DIR__ . '/**/Tests',
    ]);
};
