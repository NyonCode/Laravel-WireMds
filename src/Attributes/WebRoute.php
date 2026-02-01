<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Attributes;

use Attribute;

/**
 * Defines routing configuration for Livewire components.
 * 
 * This attribute serves as the primary source of truth for route registration.
 * It supports URL parameters with regex constraints and zone-based routing.
 *
 * @example Basic usage:
 * #[WebRoute('/dashboard', name: 'dashboard', zone: 'admin')]
 * 
 * @example With parameters and constraints:
 * #[WebRoute('/products/{id}/{slug?}', name: 'products.show', zone: 'frontend', where: ['id' => '[0-9]+'])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class WebRoute
{
    /**
     * @param string $uri The URI pattern (e.g., '/users/{id}')
     * @param string|null $name Optional route name (auto-generated if not provided)
     * @param string $zone Zone identifier (frontend, admin, customer)
     * @param array<string> $middleware Additional middleware to apply
     * @param array<string, string> $where Regex constraints for URL parameters
     * @param array<string> $methods HTTP methods (default: GET only for Livewire)
     * @param string|null $domain Optional domain constraint
     */
    public function __construct(
        public string $uri,
        public ?string $name = null,
        public string $zone = 'frontend',
        public array $middleware = [],
        public array $where = [],
        public array $methods = ['GET'],
        public ?string $domain = null,
    ) {}

    /**
     * Extract parameter names from URI pattern.
     * 
     * @return array<string>
     */
    public function getParameters(): array
    {
        preg_match_all('/\{(\w+)\??}/', $this->uri, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Check if URI has required parameters.
     */
    public function hasRequiredParameters(): bool
    {
        return (bool) preg_match('/\{\w+}/', str_replace('?}', '', $this->uri));
    }

    /**
     * Get the full URI with zone prefix applied.
     */
    public function getFullUri(string $zonePrefix = ''): string
    {
        $prefix = rtrim($zonePrefix, '/');
        $uri = '/' . ltrim($this->uri, '/');
        
        return $prefix ? $prefix . $uri : $uri;
    }

    /**
     * Convert to array for manifest serialization.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'name' => $this->name,
            'zone' => $this->zone,
            'middleware' => $this->middleware,
            'where' => $this->where,
            'methods' => $this->methods,
            'domain' => $this->domain,
            'parameters' => $this->getParameters(),
            'has_required_params' => $this->hasRequiredParameters(),
        ];
    }
}
