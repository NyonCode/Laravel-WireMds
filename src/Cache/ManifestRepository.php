<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Cache;

use NyonCode\WireMds\DiscoveryEngine;
use Illuminate\Support\Facades\File;

/**
 * Repository for accessing the discovery manifest.
 * 
 * In production, this reads from the cached PHP manifest.
 * In development, it can use the engine directly.
 */
class ManifestRepository
{
    /**
     * Loaded manifest data.
     * 
     * @var array<string, array<string, mixed>>|null
     */
    protected ?array $manifest = null;

    /**
     * Indexed lookups.
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $indexes = [];

    public function __construct(
        protected DiscoveryEngine $engine,
    ) {}

    /**
     * Get the full manifest.
     * 
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->getManifest();
    }

    /**
     * Get a single component by route name.
     * 
     * @return array<string, mixed>|null
     */
    public function get(string $routeName): ?array
    {
        return $this->getManifest()[$routeName] ?? null;
    }

    /**
     * Find component by full URI.
     * 
     * @return array<string, mixed>|null
     */
    public function findByUri(string $uri): ?array
    {
        $index = $this->getIndex('uri');
        return $index[$uri] ?? null;
    }

    /**
     * Find component by class name.
     * 
     * @return array<string, mixed>|null
     */
    public function findByClass(string $className): ?array
    {
        $index = $this->getIndex('class');
        return $index[$className] ?? null;
    }

    /**
     * Get all components in a specific zone.
     * 
     * @return array<string, array<string, mixed>>
     */
    public function getByZone(string $zone): array
    {
        return array_filter(
            $this->getManifest(),
            fn($item) => ($item['route']['zone'] ?? '') === $zone
        );
    }

    /**
     * Get all public routes (for sitemap).
     * 
     * @return array<string, array<string, mixed>>
     */
    public function getPublicRoutes(): array
    {
        $excludeZones = config('discovery.sitemap.exclude_zones', ['admin', 'customer', 'api']);

        return array_filter(
            $this->getManifest(),
            function ($item) use ($excludeZones) {
                $zone = $item['route']['zone'] ?? 'frontend';
                $isPublic = $item['access']['is_public'] ?? true;
                $sitemapEligible = $item['seo']['sitemap_eligible'] ?? true;

                return $isPublic 
                    && $sitemapEligible 
                    && !in_array($zone, $excludeZones);
            }
        );
    }

    /**
     * Get all navigation items.
     * 
     * @return array<string, array<string, mixed>>
     */
    public function getNavigationItems(): array
    {
        return array_filter(
            $this->getManifest(),
            fn($item) => isset($item['navigation']) 
                && !($item['navigation']['hidden'] ?? false)
        );
    }

    /**
     * Get navigation items for a specific zone.
     * 
     * @return array<string, array<string, mixed>>
     */
    public function getNavigationForZone(string $zone): array
    {
        return array_filter(
            $this->getNavigationItems(),
            fn($item) => ($item['route']['zone'] ?? 'frontend') === $zone
        );
    }

    /**
     * Check if manifest is cached.
     */
    public function isCached(): bool
    {
        $cachePath = config('discovery.cache.path');
        return $cachePath && File::exists($cachePath);
    }

    /**
     * Clear the in-memory manifest cache.
     */
    public function clear(): void
    {
        $this->manifest = null;
        $this->indexes = [];
    }

    /**
     * Get the manifest (from cache or fresh).
     * 
     * @return array<string, array<string, mixed>>
     */
    protected function getManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        if ($this->shouldUseCache()) {
            $this->manifest = $this->loadFromCache();
        } else {
            $this->manifest = $this->engine->discover();
        }

        return $this->manifest;
    }

    /**
     * Check if cache should be used.
     */
    protected function shouldUseCache(): bool
    {
        return config('discovery.cache.enabled', false) && $this->isCached();
    }

    /**
     * Load manifest from cache file.
     * 
     * @return array<string, array<string, mixed>>
     */
    protected function loadFromCache(): array
    {
        $cachePath = config('discovery.cache.path');
        
        if (!$cachePath || !File::exists($cachePath)) {
            return [];
        }

        return require $cachePath;
    }

    /**
     * Get or build an index.
     * 
     * @return array<string, array<string, mixed>>
     */
    protected function getIndex(string $type): array
    {
        if (isset($this->indexes[$type])) {
            return $this->indexes[$type];
        }

        $manifest = $this->getManifest();
        $index = [];

        foreach ($manifest as $name => $item) {
            $key = match ($type) {
                'uri' => $item['route']['full_uri'] ?? null,
                'class' => $item['component']['class'] ?? null,
                default => null,
            };

            if ($key !== null) {
                $index[$key] = $item;
            }
        }

        $this->indexes[$type] = $index;
        return $index;
    }
}
