<?php

declare(strict_types=1);

namespace Framework;

use Framework\Doctrine\ExcludeTestFoldersFromMappingPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * The only kernel. Its wiring (bundles, services, routes, packages) lives in
 * config/ and reads modules.php, so a new module needs no edit here.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        /** @var array{namespace: string, modules: list<string>} $product */
        $product = require $this->getProjectDir() . '/modules.php';

        $container->addCompilerPass(new ExcludeTestFoldersFromMappingPass(array_map(
            fn(string $module): string => $this->getProjectDir() . '/' . $product['namespace'] . '/' . $module,
            $product['modules'],
        )));
    }
}
