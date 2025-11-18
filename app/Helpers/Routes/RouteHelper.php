<?php

declare(strict_types=1);

namespace App\Helpers\Routes;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class RouteHelper
{
    public static function includeRouteFiles(string $directory): void
    {
        $directoryIterator = new RecursiveDirectoryIterator($directory);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->isReadable() && $file->getExtension() === 'php') {
                require $file->getPathname();
            }
        }
    }
}
