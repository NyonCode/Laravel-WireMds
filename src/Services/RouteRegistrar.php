<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Services;

use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/**
 * Route Registrar Service
 * 
 * Dynamically registers routes from the discovery manifest.
 * Handles middleware, constraints, and Livewire component binding.
 */
class RouteRegistrar
{
    public function __construct(
        protected ManifestRepository $repository,
    ) {}

    /**
     * Register all discovered routes.
     */
    public function register(): void
    {
        $manifest = $this->repository->all();

        foreach ($manifest as $name => $item) {
            $this->registerRoute($item);
        }
    }

    /**
     * Register routes for a specific zone only.
     */
    public function registerZone(string $zone): void
    {
        $manifest = $this->repository->getByZone($zone);

        foreach ($manifest as $name => $item) {
            $this->registerRoute($item);
        }
    }

    /**
     * Register a single route from manifest item.
     * 
     * @param array<string, mixed> $item
     */
    protected function registerRoute(array $item): void
    {
        $route = $item['route'] ?? [];
        $component = $item['component'] ?? [];

        $uri = $route['full_uri'] ?? null;
        $name = $route['final_name'] ?? null;
        $className = $component['class'] ?? null;

        if (!$uri || !$className) {
            return;
        }

        // Get middleware
        $middleware = $item['middleware'] ?? ['web'];

        // Get HTTP methods
        $methods = $route['methods'] ?? ['GET'];

        // Build route
        $routeBuilder = Route::match($methods, $uri, $className);

        // Apply name
        if ($name) {
            $routeBuilder->name($name);
        }

        // Apply middleware
        if (!empty($middleware)) {
            $routeBuilder->middleware($middleware);
        }

        // Apply constraints
        $where = $route['where'] ?? [];
        foreach ($where as $param => $pattern) {
            $routeBuilder->where($param, $pattern);
        }

        // Apply domain if specified
        if ($domain = $route['domain'] ?? null) {
            $routeBuilder->domain($domain);
        }
    }

    /**
     * Get all registered route names.
     * 
     * @return array<string>
     */
    public function getRegisteredRoutes(): array
    {
        return array_keys($this->repository->all());
    }

    /**
     * Check if a specific route is registered.
     */
    public function hasRoute(string $name): bool
    {
        return $this->repository->get($name) !== null;
    }
}
