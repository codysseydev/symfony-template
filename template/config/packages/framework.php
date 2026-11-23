<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'http_method_override' => false,
        'handle_all_throwables' => true,
        'php_errors' => ['log' => true],
        'messenger' => [
            // Two buses. Handlers pick one with #[AsMessageHandler(bus: ...)].
            'default_bus' => 'command.bus',
            'buses' => [
                'command.bus' => [
                    'middleware' => ['doctrine_transaction'],
                ],
                'query.bus' => [],
            ],
            // Messages routed here run in the `queue` container (messenger:consume async).
            'transports' => [
                'async' => ['dsn' => '%env(MESSENGER_TRANSPORT_DSN)%'],
            ],
        ],
    ]);

    if ('test' === $container->env()) {
        $container->extension('framework', [
            'test' => true,
        ]);
    }
};
