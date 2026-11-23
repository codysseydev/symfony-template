<?php

declare(strict_types=1);

namespace Framework\Doctrine;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Doctrine's attribute driver `require_once`s every PHP file under a mapping
 * directory. A module is mapped from its root, so the driver would also load
 * the `Tests/` classes, which extend dev-only classes (PHPUnit, WebTestCase) and
 * fatal in an install made with `composer install --no-dev`. The bundle has no
 * exclude option, so this pass adds the `Tests/` and `Migrations/` folders of
 * every module to the driver's exclude paths.
 */
final readonly class ExcludeTestFoldersFromMappingPass implements CompilerPassInterface
{
    private const array EXCLUDED_FOLDER_NAMES = ['Tests', 'Migrations'];

    /**
     * @param list<string> $moduleDirectories absolute paths of the module folders
     */
    public function __construct(private array $moduleDirectories) {}

    public function process(ContainerBuilder $container): void
    {
        $excluded = $this->excludedFolders();

        if ([] === $excluded) {
            return;
        }

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            if (null === $class) {
                continue;
            }

            $class = $container->getParameterBag()->resolveValue($class);

            // Compared by name, never autoloaded: a definition may name a class whose
            // optional dependencies are not installed.
            if (is_string($class) && AttributeDriver::class === ltrim($class, '\\')) {
                $definition->addMethodCall('addExcludePaths', [$excluded]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function excludedFolders(): array
    {
        /** @var list<string> $folders */
        $folders = [];

        foreach ($this->moduleDirectories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
            );

            /** @var \SplFileInfo $entry */
            foreach ($iterator as $entry) {
                if ($entry->isDir() && in_array($entry->getFilename(), self::EXCLUDED_FOLDER_NAMES, true)) {
                    $folders[] = $entry->getPathname();
                }
            }
        }

        sort($folders);

        return $folders;
    }
}
