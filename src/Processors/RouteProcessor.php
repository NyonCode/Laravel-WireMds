<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Processors;

use NyonCode\WireMds\Attributes\WebRoute;
use ReflectionClass;

/**
 * Processes WebRoute attributes and extracts routing metadata.
 */
class RouteProcessor extends AbstractProcessor
{
    protected int $defaultPriority = 10;

    /**
     * {@inheritdoc}
     */
    public function shouldProcess(ReflectionClass $reflection): bool
    {
        return $this->hasAttribute($reflection, WebRoute::class);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ReflectionClass $reflection, array $data): array
    {
        $route = $this->getAttribute($reflection, WebRoute::class);

        if ($route === null) {
            return $data;
        }

        $zoneConfig = $this->getZoneConfig($route->zone);
        
        $data['route'] = [
            ...$route->toArray(),
            'full_uri' => $route->getFullUri($zoneConfig['prefix'] ?? ''),
            'generated_name' => $this->generateRouteName($reflection, $route),
            'final_name' => $route->name ?? $this->generateRouteName($reflection, $route),
            'zone_config' => $zoneConfig,
        ];

        $data['component'] = [
            'class' => $reflection->getName(),
            'short_name' => $this->getShortClassName($reflection),
            'file' => $reflection->getFileName(),
            'namespace' => $reflection->getNamespaceName(),
        ];

        return $data;
    }

    /**
     * Generate route name from class if not provided.
     */
    protected function generateRouteName(ReflectionClass $reflection, WebRoute $route): string
    {
        $config = config('discovery.route_naming', 'class');

        if ($config === 'uri') {
            return $this->generateFromUri($route);
        }

        return $this->generateFromClass($reflection, $route);
    }

    /**
     * Generate route name from class name.
     */
    protected function generateFromClass(ReflectionClass $reflection, WebRoute $route): string
    {
        $parts = [];
        
        // Add zone prefix
        if ($route->zone !== 'frontend') {
            $parts[] = $route->zone;
        }

        // Get relative namespace from App\Livewire
        $namespace = $reflection->getNamespaceName();
        $basePath = 'App\\Livewire';
        
        if (str_starts_with($namespace, $basePath)) {
            $relative = substr($namespace, strlen($basePath) + 1);
            if ($relative) {
                $relativeParts = explode('\\', $relative);
                $parts = array_merge($parts, array_map(
                    fn($part) => $this->classNameToSlug($part),
                    $relativeParts
                ));
            }
        }

        // Add class name
        $parts[] = $this->classNameToSlug($this->getShortClassName($reflection));

        return implode('.', $parts);
    }

    /**
     * Generate route name from URI.
     */
    protected function generateFromUri(WebRoute $route): string
    {
        $uri = trim($route->uri, '/');
        
        // Remove parameter placeholders
        $uri = preg_replace('/\{[^}]+\}/', '', $uri);
        
        // Convert to dot notation
        $parts = array_filter(explode('/', $uri));
        
        if ($route->zone !== 'frontend') {
            array_unshift($parts, $route->zone);
        }

        return implode('.', $parts);
    }

    /**
     * Get zone configuration.
     * 
     * @return array<string, mixed>
     */
    protected function getZoneConfig(string $zone): array
    {
        return config("discovery.zones.{$zone}", []);
    }
}
