<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Traits;

use NyonCode\WireMds\Attributes\Access;
use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Livewire Guard Trait
 * 
 * Automatically verifies permissions defined in the #[Access] attribute
 * during component mount. Use this trait in Livewire components to
 * enforce access control.
 *
 * @example
 * use NyonCode\WireMds\Traits\WithDiscoveryAccess;
 * 
 * #[WebRoute('/admin/users', zone: 'admin')]
 * #[Access(permission: 'users.view')]
 * class UsersList extends Component
 * {
 *     use WithDiscoveryAccess;
 * }
 */
trait WithDiscoveryAccess
{
    /**
     * Boot the trait and verify access.
     * 
     * This method is automatically called by Livewire before mount().
     */
    public function bootWithDiscoveryAccess(): void
    {
        $this->verifyDiscoveryAccess();
    }

    /**
     * Verify that the current user has access to this component.
     * 
     * @throws AccessDeniedHttpException
     * @throws HttpException
     */
    protected function verifyDiscoveryAccess(): void
    {
        $accessData = $this->getDiscoveryAccessData();

        if ($accessData === null) {
            // No access restrictions defined
            return;
        }

        // Check authentication first
        if ($accessData['authenticated'] ?? false) {
            $guard = $accessData['guard'] ?? null;
            
            if (!Auth::guard($guard)->check()) {
                $this->handleUnauthorized($accessData);
                return;
            }
        }

        // Check permissions
        $permissions = $accessData['permissions_array'] ?? [];
        if (!empty($permissions)) {
            $this->verifyPermissions($permissions, $accessData);
        }

        // Check roles
        $roles = $accessData['roles_array'] ?? [];
        if (!empty($roles)) {
            $this->verifyRoles($roles, $accessData);
        }
    }

    /**
     * Get access data from the discovery manifest.
     * 
     * @return array<string, mixed>|null
     */
    protected function getDiscoveryAccessData(): ?array
    {
        // First, try to get from manifest
        $repository = app(ManifestRepository::class);
        $component = $repository->findByClass(static::class);

        if ($component && isset($component['access'])) {
            return $component['access'];
        }

        // Fallback: read from attribute directly
        return $this->getAccessFromAttribute();
    }

    /**
     * Read access data directly from attribute (fallback).
     * 
     * @return array<string, mixed>|null
     */
    protected function getAccessFromAttribute(): ?array
    {
        $reflection = new \ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(Access::class);

        if (empty($attributes)) {
            return null;
        }

        /** @var Access $access */
        $access = $attributes[0]->newInstance();
        return $access->toArray();
    }

    /**
     * Verify user has required permissions.
     * 
     * @param array<string> $permissions
     * @param array<string, mixed> $accessData
     */
    protected function verifyPermissions(array $permissions, array $accessData): void
    {
        $user = Auth::guard($accessData['guard'] ?? null)->user();

        if (!$user) {
            $this->handleAccessDenied($accessData);
            return;
        }

        // Check for Spatie Permission methods
        if (!method_exists($user, 'hasAnyPermission')) {
            // Fallback for non-Spatie implementations
            $this->handleAccessDenied($accessData);
            return;
        }

        $require = $accessData['require'] ?? 'all';
        $hasWildcards = $accessData['has_wildcards'] ?? false;

        // Expand wildcards if necessary
        if ($hasWildcards) {
            $permissions = $this->expandWildcardPermissions($permissions, $user);
        }

        $hasAccess = match ($require) {
            'any' => $user->hasAnyPermission($permissions),
            'all' => $user->hasAllPermissions($permissions),
            default => $user->hasAllPermissions($permissions),
        };

        if (!$hasAccess) {
            $this->handleAccessDenied($accessData);
        }
    }

    /**
     * Verify user has required roles.
     * 
     * @param array<string> $roles
     * @param array<string, mixed> $accessData
     */
    protected function verifyRoles(array $roles, array $accessData): void
    {
        $user = Auth::guard($accessData['guard'] ?? null)->user();

        if (!$user) {
            $this->handleAccessDenied($accessData);
            return;
        }

        // Check for Spatie Permission methods
        if (!method_exists($user, 'hasAnyRole')) {
            $this->handleAccessDenied($accessData);
            return;
        }

        $require = $accessData['require'] ?? 'all';

        $hasAccess = match ($require) {
            'any' => $user->hasAnyRole($roles),
            'all' => $user->hasAllRoles($roles),
            default => $user->hasAllRoles($roles),
        };

        if (!$hasAccess) {
            $this->handleAccessDenied($accessData);
        }
    }

    /**
     * Expand wildcard permissions against user's available permissions.
     * 
     * @param array<string> $permissions
     * @param mixed $user
     * @return array<string>
     */
    protected function expandWildcardPermissions(array $permissions, $user): array
    {
        if (!method_exists($user, 'getAllPermissions')) {
            return $permissions;
        }

        $allPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        $expanded = [];

        foreach ($permissions as $permission) {
            if (!str_contains($permission, '*')) {
                $expanded[] = $permission;
                continue;
            }

            $pattern = '/^' . str_replace(['\\*', '\\.'], ['.*', '\\.'], preg_quote($permission, '/')) . '$/';
            
            foreach ($allPermissions as $available) {
                if (preg_match($pattern, $available)) {
                    $expanded[] = $available;
                }
            }
        }

        return array_unique($expanded);
    }

    /**
     * Handle unauthorized (not authenticated) access.
     * 
     * @param array<string, mixed> $accessData
     */
    protected function handleUnauthorized(array $accessData): void
    {
        $redirectTo = $accessData['redirect_to'] ?? null;

        if ($redirectTo) {
            redirect()->route($redirectTo)->send();
            return;
        }

        abort(401, 'Unauthenticated.');
    }

    /**
     * Handle access denied (authenticated but no permission).
     * 
     * @param array<string, mixed> $accessData
     */
    protected function handleAccessDenied(array $accessData): void
    {
        $httpCode = $accessData['http_code'] ?? 403;
        $redirectTo = $accessData['redirect_to'] ?? null;

        if ($redirectTo) {
            redirect()->route($redirectTo)->send();
            return;
        }

        abort($httpCode, 'Access denied.');
    }
}
