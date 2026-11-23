<?php

declare(strict_types=1);

/**
 * The one list of product modules.
 *
 * The service config (config/services.php), the route config (config/routes.php),
 * the Doctrine mapping (config/packages/doctrine.php), the migrations config
 * (config/packages/doctrine_migrations.php), PHPArkitect (phparkitect.php),
 * PHPStan (phpstan.modules.php), Rector (rector.php), PHP CS Fixer
 * (.php-cs-fixer.dist.php) and the module isolation rule all read this file.
 * PHPUnit scans the namespace folder, and the architecture test ModuleListTest
 * fails when a folder and this list disagree.
 *
 * A module is a folder `<namespace>/<Module>/` with four fixed parts:
 * `<module>-services.php` (a ContainerConfigurator closure imported by
 * config/services.php), `<module>-routes.php` (a RoutingConfigurator closure
 * imported by config/routes.php), `Migrations/` and its slices. `<module>` is
 * the module name in lowercase.
 *
 * Add a module here, create those files (the new-module skill does it), and
 * every gate picks it up.
 *
 * @return array{namespace: string, modules: list<string>}
 */
return [
    'namespace' => '{{PRODUCT_NAMESPACE}}',
    'modules' => [
        'Health',
    ],
];
