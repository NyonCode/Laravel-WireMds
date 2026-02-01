<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Attributes;

use Attribute;

/**
 * Defines navigation and menu configuration for UI generation.
 * 
 * This attribute provides metadata for automatic menu building,
 * supporting hierarchical grouping, icons, and custom sorting.
 *
 * @example Basic usage:
 * #[Navigation(label: 'Dashboard', icon: 'home', sort: 10)]
 * 
 * @example With group hierarchy:
 * #[Navigation(label: 'Users List', group: 'Settings.Users', icon: 'users', sort: 20)]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Navigation
{
    /**
     * @param string $label Display label in navigation
     * @param string|null $group Hierarchical group path (e.g., 'Settings.Users')
     * @param string|null $icon Icon identifier (Heroicons, FontAwesome, etc.)
     * @param int $sort Sort order within group (lower = higher priority)
     * @param bool $hidden Hide from navigation but keep accessible
     * @param string|null $badge Badge text or count (e.g., 'NEW', '5')
     * @param string|null $badgeColor Badge color class
     * @param string|null $parent Parent route name for breadcrumb hierarchy
     * @param array<string, mixed> $meta Additional metadata for custom navigation renderers
     */
    public function __construct(
        public string $label,
        public ?string $group = null,
        public ?string $icon = null,
        public int $sort = 100,
        public bool $hidden = false,
        public ?string $badge = null,
        public ?string $badgeColor = null,
        public ?string $parent = null,
        public array $meta = [],
    ) {}

    /**
     * Get group segments as array.
     * 
     * @return array<string>
     */
    public function getGroupSegments(): array
    {
        if ($this->group === null) {
            return [];
        }

        return array_filter(explode('.', $this->group));
    }

    /**
     * Get the top-level group name.
     */
    public function getRootGroup(): ?string
    {
        $segments = $this->getGroupSegments();
        return $segments[0] ?? null;
    }

    /**
     * Check if this item belongs to a specific group path.
     */
    public function belongsToGroup(string $groupPath): bool
    {
        if ($this->group === null) {
            return false;
        }

        return str_starts_with($this->group, $groupPath);
    }

    /**
     * Get translation key for label if using Laravel's translation system.
     */
    public function getTranslationKey(string $prefix = 'navigation'): string
    {
        $key = strtolower(str_replace([' ', '.'], ['_', '.'], $this->label));
        return "{$prefix}.{$key}";
    }

    /**
     * Convert to array for manifest serialization.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'group' => $this->group,
            'group_segments' => $this->getGroupSegments(),
            'icon' => $this->icon,
            'sort' => $this->sort,
            'hidden' => $this->hidden,
            'badge' => $this->badge,
            'badge_color' => $this->badgeColor,
            'parent' => $this->parent,
            'meta' => $this->meta,
        ];
    }
}
