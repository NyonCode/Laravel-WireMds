<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Services;

use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Navigation Builder Service
 * 
 * Builds hierarchical navigation menus from discovery manifest.
 * Supports grouping, sorting, active state detection, and permission filtering.
 */
class NavigationBuilder
{
    public function __construct(
        protected ManifestRepository $repository,
    ) {}

    /**
     * Build navigation for a specific zone.
     * 
     * @param string $zone Zone name (admin, customer, frontend)
     * @param bool $filterByPermissions Apply permission checks
     * @return Collection<int, array<string, mixed>>
     */
    public function forZone(string $zone, bool $filterByPermissions = true): Collection
    {
        $cacheKey = config('discovery.navigation.cache_key', 'discovery.navigation') 
            . ".{$zone}." . ($filterByPermissions ? 'filtered' : 'all');
        
        $items = $this->repository->getNavigationForZone($zone);

        if ($filterByPermissions) {
            $items = $this->filterByPermissions($items);
        }

        return $this->buildTree($items);
    }

    /**
     * Build navigation for all zones.
     * 
     * @param bool $filterByPermissions Apply permission checks
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function all(bool $filterByPermissions = true): Collection
    {
        $zones = array_keys(config('discovery.zones', []));
        
        return collect($zones)->mapWithKeys(function ($zone) use ($filterByPermissions) {
            return [$zone => $this->forZone($zone, $filterByPermissions)];
        });
    }

    /**
     * Get flat list of navigation items for a zone.
     * 
     * @param string $zone
     * @param bool $filterByPermissions
     * @return Collection<int, array<string, mixed>>
     */
    public function flatForZone(string $zone, bool $filterByPermissions = true): Collection
    {
        $items = $this->repository->getNavigationForZone($zone);

        if ($filterByPermissions) {
            $items = $this->filterByPermissions($items);
        }

        return collect($items)
            ->sortBy(fn($item) => $item['navigation']['sort'] ?? 100)
            ->values()
            ->map(fn($item) => $this->formatNavigationItem($item));
    }

    /**
     * Build hierarchical tree structure.
     * 
     * @param array<string, array<string, mixed>> $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildTree(array $items): Collection
    {
        $groups = [];
        $ungrouped = [];

        foreach ($items as $name => $item) {
            $nav = $item['navigation'] ?? [];
            $groupPath = $nav['group'] ?? null;

            if ($groupPath) {
                $this->addToGroup($groups, $groupPath, $item);
            } else {
                $ungrouped[] = $this->formatNavigationItem($item);
            }
        }

        // Convert groups to array structure
        $result = $this->flattenGroups($groups);

        // Add ungrouped items
        foreach ($ungrouped as $item) {
            $result[] = $item;
        }

        // Sort by sort order
        usort($result, fn($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

        return collect($result);
    }

    /**
     * Add item to nested group structure.
     * 
     * @param array<string, mixed> $groups
     * @param string $path
     * @param array<string, mixed> $item
     */
    protected function addToGroup(array &$groups, string $path, array $item): void
    {
        $segments = explode('.', $path);
        $current = &$groups;

        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                $current[$segment] = [
                    'label' => $segment,
                    'children' => [],
                    'items' => [],
                    'sort' => 100,
                ];
            }
            $current = &$current[$segment];
        }

        $current['items'][] = $this->formatNavigationItem($item);
        
        // Update sort order to minimum of all items
        $itemSort = $item['navigation']['sort'] ?? 100;
        $current['sort'] = min($current['sort'], $itemSort);
    }

    /**
     * Flatten nested groups into array structure.
     * 
     * @param array<string, mixed> $groups
     * @param int $depth
     * @return array<int, array<string, mixed>>
     */
    protected function flattenGroups(array $groups, int $depth = 0): array
    {
        $maxDepth = config('discovery.navigation.max_depth', 3);
        $result = [];

        foreach ($groups as $name => $group) {
            if (!is_array($group)) {
                continue;
            }

            $entry = [
                'type' => 'group',
                'label' => $group['label'] ?? $name,
                'sort' => $group['sort'] ?? 100,
                'depth' => $depth,
                'children' => [],
            ];

            // Add nested groups
            if ($depth < $maxDepth - 1 && !empty($group['children'])) {
                $entry['children'] = array_merge(
                    $entry['children'],
                    $this->flattenGroups($group['children'], $depth + 1)
                );
            }

            // Add items
            foreach ($group['items'] ?? [] as $item) {
                $item['depth'] = $depth + 1;
                $entry['children'][] = $item;
            }

            // Sort children
            usort($entry['children'], fn($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Format a manifest item as navigation item.
     * 
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    protected function formatNavigationItem(array $item): array
    {
        $nav = $item['navigation'] ?? [];
        $route = $item['route'] ?? [];

        return [
            'type' => 'item',
            'label' => $nav['label'] ?? 'Unknown',
            'route_name' => $route['final_name'] ?? null,
            'url' => $this->generateUrl($item),
            'icon' => $nav['icon'] ?? null,
            'sort' => $nav['sort'] ?? 100,
            'badge' => $nav['badge'] ?? null,
            'badge_color' => $nav['badge_color'] ?? null,
            'active' => $this->isActive($item),
            'has_params' => $route['has_required_params'] ?? false,
            'meta' => $nav['meta'] ?? [],
        ];
    }

    /**
     * Generate URL for navigation item.
     * 
     * @param array<string, mixed> $item
     */
    protected function generateUrl(array $item): ?string
    {
        $routeName = $item['route']['final_name'] ?? null;

        if (!$routeName) {
            return null;
        }

        // Don't generate URL for routes with required params
        if ($item['route']['has_required_params'] ?? false) {
            return null;
        }

        if (!Route::has($routeName)) {
            return null;
        }

        try {
            return route($routeName);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Check if navigation item is currently active.
     * 
     * @param array<string, mixed> $item
     */
    protected function isActive(array $item): bool
    {
        $routeName = $item['route']['final_name'] ?? null;
        $currentRoute = Route::currentRouteName();

        if (!$routeName || !$currentRoute) {
            return false;
        }

        // Exact match
        if ($routeName === $currentRoute) {
            return true;
        }

        // Check if current route starts with this route name (parent active)
        return str_starts_with($currentRoute, $routeName . '.');
    }

    /**
     * Filter items by user permissions.
     * 
     * @param array<string, array<string, mixed>> $items
     * @return array<string, array<string, mixed>>
     */
    protected function filterByPermissions(array $items): array
    {
        $user = Auth::user();

        return array_filter($items, function ($item) use ($user) {
            $access = $item['access'] ?? [];

            // Public items are always visible
            if ($access['is_public'] ?? true) {
                return true;
            }

            // No user = no access to protected items
            if (!$user) {
                return false;
            }

            // Check permissions
            $permissions = $access['permissions_array'] ?? [];
            if (!empty($permissions)) {
                if (!method_exists($user, 'hasAnyPermission')) {
                    return false;
                }
                if (!$user->hasAnyPermission($permissions)) {
                    return false;
                }
            }

            // Check roles
            $roles = $access['roles_array'] ?? [];
            if (!empty($roles)) {
                if (!method_exists($user, 'hasAnyRole')) {
                    return false;
                }
                if (!$user->hasAnyRole($roles)) {
                    return false;
                }
            }

            return true;
        });
    }
}
