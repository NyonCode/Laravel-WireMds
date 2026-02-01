<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Services;

use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Breadcrumb Service
 * 
 * Generates intelligent breadcrumb navigation based on:
 * - URI structure hierarchy
 * - Navigation group hierarchy
 * - Parent relationships defined in Navigation attribute
 * 
 * Supports dynamic label replacement for parameterized routes.
 */
class BreadcrumbService
{
    /**
     * Dynamic label resolvers.
     * 
     * @var array<string, callable>
     */
    protected array $resolvers = [];

    public function __construct(
        protected ManifestRepository $repository,
    ) {}

    /**
     * Generate breadcrumbs for the current route.
     * 
     * @param array<string, mixed> $parameters Dynamic parameter values for labels
     * @return Collection<int, array{label: string, url: string|null, active: bool}>
     */
    public function generate(array $parameters = []): Collection
    {
        $currentRoute = Route::currentRouteName();
        
        if (!$currentRoute) {
            return $this->getHomeBreadcrumb();
        }

        $current = $this->repository->get($currentRoute);

        if (!$current) {
            return $this->getHomeBreadcrumb();
        }

        $breadcrumbs = collect();

        // Add home
        $breadcrumbs->push($this->createBreadcrumb(
            config('discovery.breadcrumbs.home_label', 'Home'),
            route(config('discovery.breadcrumbs.home_route', 'home')),
            false
        ));

        // Build the chain
        $chain = $this->buildBreadcrumbChain($current, $parameters);

        foreach ($chain as $item) {
            $breadcrumbs->push($item);
        }

        // Mark last as active
        if ($breadcrumbs->isNotEmpty()) {
            $breadcrumbs = $breadcrumbs->map(function ($item, $index) use ($breadcrumbs) {
                $item['active'] = $index === $breadcrumbs->count() - 1;
                return $item;
            });
        }

        return $breadcrumbs;
    }

    /**
     * Register a dynamic label resolver.
     * 
     * @param string $routeName Route name pattern (supports wildcards)
     * @param callable $resolver Receives (string $routeName, array $parameters) -> string
     */
    public function registerResolver(string $routeName, callable $resolver): self
    {
        $this->resolvers[$routeName] = $resolver;
        return $this;
    }

    /**
     * Build the breadcrumb chain for a component.
     * 
     * @param array<string, mixed> $current
     * @param array<string, mixed> $parameters
     * @return array<int, array{label: string, url: string|null, active: bool}>
     */
    protected function buildBreadcrumbChain(array $current, array $parameters): array
    {
        $chain = [];

        // Check for explicit parent relationship
        $parentName = $current['navigation']['parent'] ?? null;
        
        if ($parentName) {
            $parent = $this->repository->get($parentName);
            if ($parent) {
                $chain = array_merge(
                    $this->buildBreadcrumbChain($parent, $parameters),
                    $chain
                );
            }
        } else {
            // Build from URI structure
            $chain = $this->buildFromUriStructure($current, $parameters);
        }

        // Add current item
        $chain[] = $this->createBreadcrumb(
            $this->resolveLabel($current, $parameters),
            $this->generateUrl($current, $parameters),
            true
        );

        return $chain;
    }

    /**
     * Build breadcrumbs from URI structure.
     * 
     * @param array<string, mixed> $current
     * @param array<string, mixed> $parameters
     * @return array<int, array{label: string, url: string|null, active: bool}>
     */
    protected function buildFromUriStructure(array $current, array $parameters): array
    {
        $chain = [];
        $uri = $current['route']['full_uri'] ?? '';
        $segments = array_filter(explode('/', trim($uri, '/')));

        // Remove parameters from segments
        $segments = array_filter($segments, fn($s) => !str_starts_with($s, '{'));

        // Build partial URIs and find matching routes
        $partialUri = '';
        
        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                // Skip the last segment (current page)
                continue;
            }

            $partialUri .= '/' . $segment;
            
            $match = $this->repository->findByUri($partialUri);
            
            if ($match) {
                $chain[] = $this->createBreadcrumb(
                    $this->resolveLabel($match, $parameters),
                    $this->generateUrl($match, $parameters),
                    false
                );
            }
        }

        return $chain;
    }

    /**
     * Resolve the label for a breadcrumb item.
     * 
     * @param array<string, mixed> $item
     * @param array<string, mixed> $parameters
     */
    protected function resolveLabel(array $item, array $parameters): string
    {
        $routeName = $item['route']['final_name'] ?? '';
        $label = $item['navigation']['label'] ?? $item['component']['short_name'] ?? 'Unknown';

        // Check for registered resolver
        foreach ($this->resolvers as $pattern => $resolver) {
            if ($this->matchesPattern($routeName, $pattern)) {
                $resolved = $resolver($routeName, $parameters);
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        // Replace placeholders in label
        foreach ($parameters as $key => $value) {
            $label = str_replace("{{$key}}", (string) $value, $label);
        }

        // Use translation if configured
        if (config('discovery.breadcrumbs.use_translation', false)) {
            $translationKey = config('discovery.breadcrumbs.translation_prefix', 'breadcrumbs') 
                . '.' . $routeName;
            
            $translated = __($translationKey, $parameters);
            
            if ($translated !== $translationKey) {
                return $translated;
            }
        }

        return $label;
    }

    /**
     * Generate URL for a breadcrumb item.
     * 
     * @param array<string, mixed> $item
     * @param array<string, mixed> $parameters
     */
    protected function generateUrl(array $item, array $parameters): ?string
    {
        $routeName = $item['route']['final_name'] ?? null;

        if (!$routeName || !Route::has($routeName)) {
            return null;
        }

        // Get required parameters for this route
        $routeParams = $item['route']['parameters'] ?? [];
        $neededParams = [];

        foreach ($routeParams as $param) {
            if (isset($parameters[$param])) {
                $neededParams[$param] = $parameters[$param];
            }
        }

        try {
            return route($routeName, $neededParams);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Create a breadcrumb array item.
     * 
     * @return array{label: string, url: string|null, active: bool}
     */
    protected function createBreadcrumb(string $label, ?string $url, bool $active): array
    {
        return [
            'label' => $label,
            'url' => $url,
            'active' => $active,
        ];
    }

    /**
     * Get just the home breadcrumb.
     * 
     * @return Collection<int, array{label: string, url: string|null, active: bool}>
     */
    protected function getHomeBreadcrumb(): Collection
    {
        return collect([
            $this->createBreadcrumb(
                config('discovery.breadcrumbs.home_label', 'Home'),
                route(config('discovery.breadcrumbs.home_route', 'home')),
                true
            ),
        ]);
    }

    /**
     * Check if route name matches a pattern.
     */
    protected function matchesPattern(string $routeName, string $pattern): bool
    {
        if ($pattern === $routeName) {
            return true;
        }

        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace(['\\*', '\\.'], ['.*', '\\.'], preg_quote($pattern, '/')) . '$/';
            return (bool) preg_match($regex, $routeName);
        }

        return false;
    }
}
