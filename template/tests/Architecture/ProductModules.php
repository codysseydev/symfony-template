<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Reads the one module list (modules.php at the repository root).
 */
final class ProductModules
{
    public static function namespace(): string
    {
        return self::load()['namespace'];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return self::load()['modules'];
    }

    /**
     * @return array{namespace: string, modules: list<string>}
     */
    private static function load(): array
    {
        /** @var array{namespace: string, modules: list<string>} $list */
        $list = require dirname(__DIR__, 2) . '/modules.php';

        return $list;
    }
}
