<?php

declare(strict_types=1);

namespace App\Helpers\Routes;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class RouteHelper
{
    public static function includeRouteFiles(string $directory): void
    {
        $directoryIterator = new RecursiveDirectoryIterator($directory);

        /** @var RecursiveDirectoryIterator|RecursiveIteratorIterator $iterator */
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        while ($iterator->valid()) {
            if (!$iterator->isDot() && $iterator->isFile() && $iterator->isReadable() && $iterator->current()->getExtension() === 'php') {
                require $iterator->key();
            }

            $iterator->next();
        }
    }
}
