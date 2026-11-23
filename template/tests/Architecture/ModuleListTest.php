<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * modules.php is hand-maintained. A module folder without an entry drops out of
 * service registration, routing, mapping, isolation and static analysis. An
 * entry without a folder breaks the container build. A module missing one of its
 * fixed files is half wired. This test keeps all of it in step, and proves the
 * kernel really loads each module's two config files.
 */
final class ModuleListTest extends KernelTestCase
{
    #[Test]
    public function itListsEveryModuleFolderAndNothingElse(): void
    {
        $folders = [];

        foreach (new \FilesystemIterator($this->root() . '/' . ProductModules::namespace(), \FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir()) {
                $folders[] = $entry->getFilename();
            }
        }

        $modules = ProductModules::names();

        sort($folders);
        sort($modules);

        self::assertSame($folders, $modules);
    }

    #[Test]
    public function itGivesEveryModuleItsFixedFiles(): void
    {
        foreach (ProductModules::names() as $module) {
            $folder = $this->moduleFolder($module);
            $lower = strtolower($module);

            self::assertFileExists($folder . '/' . $lower . '-services.php', $module . ' has no ' . $lower . '-services.php');
            self::assertFileExists($folder . '/' . $lower . '-routes.php', $module . ' has no ' . $lower . '-routes.php');
            self::assertDirectoryExists($folder . '/Migrations', $module . ' has no Migrations/ folder, which doctrine_migrations needs');
        }
    }

    #[Test]
    public function theKernelLoadsEveryModulesServiceFile(): void
    {
        self::bootKernel();

        foreach (ProductModules::names() as $module) {
            self::assertTrue(
                self::getContainer()->getParameter('module.' . strtolower($module)),
                $module . "'s services file did not run. It must set the parameter module." . strtolower($module) . '.',
            );
        }
    }

    #[Test]
    public function theKernelLoadsEveryModulesRouteFile(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        $loaded = array_map(
            static fn(object $resource): string => (string) $resource,
            $router->getRouteCollection()->getResources(),
        );

        foreach (ProductModules::names() as $module) {
            $file = realpath($this->moduleFolder($module) . '/' . strtolower($module) . '-routes.php');

            self::assertContains($file, $loaded, $module . "'s route file was not loaded");
        }
    }

    #[Test]
    public function itMapsTheProductNamespaceInComposer(): void
    {
        $composer = json_decode((string) file_get_contents($this->root() . '/composer.json'), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($composer);
        self::assertIsArray($composer['autoload']);
        self::assertIsArray($composer['autoload']['psr-4']);
        self::assertSame(ProductModules::namespace() . '/', $composer['autoload']['psr-4'][ProductModules::namespace() . '\\'] ?? null);
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function moduleFolder(string $module): string
    {
        return $this->root() . '/' . ProductModules::namespace() . '/' . $module;
    }
}
