<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class CacheService
{
    /**
     * Durées de cache par défaut (en secondes)
     */
    public const TTL_SHORT = 300;      // 5 minutes

    public const TTL_MEDIUM = 1800;    // 30 minutes

    public const TTL_LONG = 3600;      // 1 heure

    public const TTL_VERY_LONG = 86400; // 24 heures

    /**
     * Préfixes de clés de cache
     */
    public const PREFIX_POST = 'post';

    public const PREFIX_COMPANY = 'company';

    public const PREFIX_USER = 'user';

    /**
     * Génère une clé de cache unique basée sur les paramètres
     */
    public static function generateKey(string $prefix, mixed ...$params): string
    {
        $serialized = collect($params)
            ->map(fn($param) => is_array($param) ? md5(serialize($param)) : $param)
            ->implode('.');

        return "{$prefix}.{$serialized}";
    }

    /**
     * Récupère ou met en cache une valeur
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Invalide tout le cache d'un préfixe
     */
    public static function forgetPrefix(string $prefix): void
    {
        $files = glob(storage_path("framework/cache/data/*/{$prefix}.*"));

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Invalide une clé de cache spécifique
     */
    public static function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Invalide plusieurs clés de cache
     *
     * @param  array<string>  $keys
     */
    public static function forgetMany(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Vide tout le cache
     */
    public static function flush(): void
    {
        Cache::flush();
    }

    /**
     * Vérifie si une clé existe dans le cache
     */
    public static function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Récupère une valeur du cache
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Stocke une valeur dans le cache
     */
    public static function put(string $key, mixed $value, int $ttl): bool
    {
        return Cache::put($key, $value, $ttl);
    }
}
