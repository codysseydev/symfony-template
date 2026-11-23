<?php

declare(strict_types=1);

/**
 * PHPStan reads the module list from modules.php, so a new module is analysed
 * with no edit to phpstan.neon.
 */
$product = require __DIR__ . '/modules.php';

$paths = ['Framework/', 'tools/'];

foreach ($product['modules'] as $module) {
    $paths[] = $product['namespace'] . '/' . $module . '/';
}

return [
    'parameters' => [
        'paths' => $paths,
    ],
];
