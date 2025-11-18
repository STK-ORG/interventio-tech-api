<?php

declare(strict_types=1);

namespace App\Http\Traits;

use App\Services\CacheService;
use Illuminate\Http\Request;

trait Cacheable
{
    /**
     * Génère une clé de cache basée sur la requête
     */
    protected function getCacheKey(Request $request, string $prefix): string
    {
        $params = [
            $request->query->all(),
            $request->header('Accept-Language', 'en'),
            $request->user()->id ?? 'guest',
        ];

        return CacheService::generateKey($prefix, ...$params);
    }

    /**
     * Met en cache une réponse de liste paginée
     */
    protected function cacheList(Request $request, string $prefix, \Closure $callback, int $ttl = CacheService::TTL_MEDIUM): mixed
    {
        $cacheKey = $this->getCacheKey($request, $prefix . '.list');

        return CacheService::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Met en cache une ressource individuelle
     */
    protected function cacheItem(string $identifier, string $prefix, \Closure $callback, int $ttl = CacheService::TTL_LONG): mixed
    {
        $cacheKey = CacheService::generateKey($prefix . '.item', $identifier);

        return CacheService::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Invalide tout le cache d'un préfixe
     */
    protected function invalidateCache(string $prefix): void
    {
        CacheService::forgetPrefix($prefix);
    }

    /**
     * Invalide le cache d'un élément spécifique
     */
    protected function invalidateCacheItem(string $identifier, string $prefix): void
    {
        $cacheKey = CacheService::generateKey($prefix . '.item', $identifier);
        CacheService::forget($cacheKey);
    }
}
