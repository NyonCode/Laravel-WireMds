<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Attributes;

use Attribute;

/**
 * Defines access control and permission requirements.
 * 
 * Integrates with Spatie Laravel Permission package.
 * Supports wildcards (e.g., 'admin.*') and multiple permissions.
 *
 * @example Single permission:
 * #[Access(permission: 'users.view')]
 * 
 * @example Multiple permissions (any):
 * #[Access(permission: ['users.view', 'admin.*'], require: 'any')]
 * 
 * @example Role-based:
 * #[Access(roles: ['admin', 'super-admin'], require: 'any')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Access
{
    public const REQUIRE_ALL = 'all';
    public const REQUIRE_ANY = 'any';

    /**
     * @param string|array<string>|null $permission Required permission(s), supports wildcards
     * @param string|array<string>|null $roles Required role(s)
     * @param string $require 'all' = must have all, 'any' = must have at least one
     * @param string|null $guard Authentication guard to use
     * @param bool $authenticated Whether authentication is required (null = auto-detect)
     * @param string|null $redirectTo Route to redirect unauthorized users
     * @param int $httpCode HTTP status code for unauthorized (403 default)
     */
    public function __construct(
        public string|array|null $permission = null,
        public string|array|null $roles = null,
        public string $require = self::REQUIRE_ALL,
        public ?string $guard = null,
        public bool $authenticated = true,
        public ?string $redirectTo = null,
        public int $httpCode = 403,
    ) {}

    /**
     * Get permissions as array.
     * 
     * @return array<string>
     */
    public function getPermissions(): array
    {
        if ($this->permission === null) {
            return [];
        }

        return is_array($this->permission) ? $this->permission : [$this->permission];
    }

    /**
     * Get roles as array.
     * 
     * @return array<string>
     */
    public function getRoles(): array
    {
        if ($this->roles === null) {
            return [];
        }

        return is_array($this->roles) ? $this->roles : [$this->roles];
    }

    /**
     * Check if access has wildcard permissions.
     */
    public function hasWildcardPermissions(): bool
    {
        foreach ($this->getPermissions() as $permission) {
            if (str_contains($permission, '*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expand wildcard permission against available permissions.
     * 
     * @param array<string> $availablePermissions
     * @return array<string>
     */
    public function expandWildcards(array $availablePermissions): array
    {
        $expanded = [];

        foreach ($this->getPermissions() as $permission) {
            if (!str_contains($permission, '*')) {
                $expanded[] = $permission;
                continue;
            }

            $pattern = '/^' . str_replace(['\\*', '\\.'], ['.*', '\\.'], preg_quote($permission, '/')) . '$/';
            
            foreach ($availablePermissions as $available) {
                if (preg_match($pattern, $available)) {
                    $expanded[] = $available;
                }
            }
        }

        return array_unique($expanded);
    }

    /**
     * Check if this represents public access (no restrictions).
     */
    public function isPublic(): bool
    {
        return empty($this->getPermissions()) 
            && empty($this->getRoles()) 
            && !$this->authenticated;
    }

    /**
     * Get middleware array for Laravel router.
     * 
     * @return array<string>
     */
    public function toMiddleware(): array
    {
        $middleware = [];

        if ($this->authenticated) {
            $guard = $this->guard ? ":{$this->guard}" : '';
            $middleware[] = "auth{$guard}";
        }

        $permissions = $this->getPermissions();
        if (!empty($permissions)) {
            $operator = $this->require === self::REQUIRE_ANY ? '|' : ',';
            $middleware[] = 'permission:' . implode($operator, $permissions);
        }

        $roles = $this->getRoles();
        if (!empty($roles)) {
            $operator = $this->require === self::REQUIRE_ANY ? '|' : ',';
            $middleware[] = 'role:' . implode($operator, $roles);
        }

        return $middleware;
    }

    /**
     * Convert to array for manifest serialization.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'permission' => $this->permission,
            'permissions_array' => $this->getPermissions(),
            'roles' => $this->roles,
            'roles_array' => $this->getRoles(),
            'require' => $this->require,
            'guard' => $this->guard,
            'authenticated' => $this->authenticated,
            'redirect_to' => $this->redirectTo,
            'http_code' => $this->httpCode,
            'is_public' => $this->isPublic(),
            'has_wildcards' => $this->hasWildcardPermissions(),
            'middleware' => $this->toMiddleware(),
        ];
    }
}
