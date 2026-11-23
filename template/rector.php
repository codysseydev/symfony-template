<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

$product = require __DIR__ . '/modules.php';

// declare(strict_types=1) is enforced by PHP CS Fixer (.php-cs-fixer.dist.php), not Rector.
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Framework',
        ...array_map(
            static fn(string $module): string => __DIR__ . '/' . $product['namespace'] . '/' . $module,
            $product['modules'],
        ),
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/tests',
        __DIR__ . '/tools',
    ])
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(49)
    ->withCodeQualityLevel(71)
    ->withCodingStyleLevel(25);
