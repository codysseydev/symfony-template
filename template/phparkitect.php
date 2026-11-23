<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Tests\Architecture\ClassShapeRules;
use Tests\Architecture\ContainerAccessRules;
use Tests\Architecture\ExceptionRules;
use Tests\Architecture\FrameworkIsolationRules;
use Tests\Architecture\ModuleIsolationRules;
use Tests\Architecture\NamingRules;
use Tests\Architecture\ProductModules;

/*
 * Architecture rules enforced by PHPArkitect, run via `composer arkitect`.
 *
 * The module list comes from modules.php through ProductModules. Test folders
 * are not scanned.
 */
return static function (Config $config): void {
    $appCode = ClassSet::fromDir(__DIR__ . '/Framework', __DIR__ . '/' . ProductModules::namespace())
        ->excludePath('*/Tests/*')
        ->excludePath('Tests/*');

    $config->add(
        $appCode,
        ...ModuleIsolationRules::rules(),
        ...FrameworkIsolationRules::rules(),
        ...ClassShapeRules::rules(),
        ...NamingRules::rules(),
        ...ExceptionRules::rules(),
        ...ContainerAccessRules::rules(),
    );
};
