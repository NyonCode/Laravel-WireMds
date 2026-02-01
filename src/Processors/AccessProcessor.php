<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Processors;

use NyonCode\WireMds\Attributes\Access;
use ReflectionClass;

/**
 * Processes Access attributes and extracts permission metadata.
 * 
 * This processor also normalizes access rules based on zone configuration,
 * ensuring that admin zones automatically get appropriate permissions.
 */
class AccessProcessor extends AbstractProcessor
{
    protected int $defaultPriority = 30;

    /**
     * {@inheritdoc}
     */
    public function process(ReflectionClass $reflection, array $data): array
    {
        $access = $this->getAttribute($reflection, Access::class);

        $data['access'] = $access 
            ? $this->processAccess($access, $data)
            : $this->generateDefaultAccess($data);

        // Generate final middleware array
        $data['middleware'] = $this->buildMiddlewareStack($data);

        return $data;
    }

    /**
     * Process explicit access attribute.
     * 
     * @return array<string, mixed>
     */
    protected function processAccess(Access $access, array $data): array
    {
        return $access->toArray();
    }

    /**
     * Generate default access based on zone configuration.
     * 
     * @return array<string, mixed>
     */
    protected function generateDefaultAccess(array $data): array
    {
        $zoneConfig = $data['route']['zone_config'] ?? [];
        
        $defaultPermission = $zoneConfig['default_permission'] ?? null;
        $defaultRole = $zoneConfig['default_role'] ?? null;
        $guard = $zoneConfig['guard'] ?? null;

        // Determine if authentication is required
        $middleware = $zoneConfig['middleware'] ?? [];
        $requiresAuth = in_array('auth', $middleware) || 
                       !empty(array_filter($middleware, fn($m) => str_starts_with($m, 'auth:')));

        // Create synthetic Access object for consistent data structure
        $access = new Access(
            permission: $defaultPermission,
            roles: $defaultRole,
            guard: $guard,
            authenticated: $requiresAuth,
        );

        $result = $access->toArray();
        $result['auto_generated'] = true;
        $result['from_zone'] = $data['route']['zone'] ?? null;

        return $result;
    }

    /**
     * Build the final middleware stack.
     * 
     * @return array<string>
     */
    protected function buildMiddlewareStack(array $data): array
    {
        $middleware = [];

        // Start with zone middleware
        $zoneConfig = $data['route']['zone_config'] ?? [];
        $zoneMiddleware = $zoneConfig['middleware'] ?? ['web'];
        
        foreach ($zoneMiddleware as $m) {
            $middleware[] = $m;
        }

        // Add route-specific middleware
        $routeMiddleware = $data['route']['middleware'] ?? [];
        foreach ($routeMiddleware as $m) {
            if (!in_array($m, $middleware)) {
                $middleware[] = $m;
            }
        }

        // Add access middleware (permissions/roles)
        // Skip 'auth' if already in middleware to avoid duplicates
        $accessMiddleware = $data['access']['middleware'] ?? [];
        foreach ($accessMiddleware as $m) {
            if (str_starts_with($m, 'auth') && $this->hasAuthMiddleware($middleware)) {
                continue;
            }
            if (!in_array($m, $middleware)) {
                $middleware[] = $m;
            }
        }

        // Add rate limiting if configured
        if (isset($zoneConfig['rate_limit'])) {
            $middleware[] = "throttle:{$zoneConfig['rate_limit']}";
        }

        return array_unique($middleware);
    }

    /**
     * Check if middleware stack already has auth middleware.
     * 
     * @param array<string> $middleware
     */
    protected function hasAuthMiddleware(array $middleware): bool
    {
        foreach ($middleware as $m) {
            if ($m === 'auth' || str_starts_with($m, 'auth:')) {
                return true;
            }
        }
        return false;
    }
}
