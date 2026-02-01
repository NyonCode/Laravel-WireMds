<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Processors;

use NyonCode\WireMds\Attributes\Navigation;
use ReflectionClass;

/**
 * Processes Navigation attributes and extracts menu metadata.
 */
class NavigationProcessor extends AbstractProcessor
{
    protected int $defaultPriority = 20;

    /**
     * {@inheritdoc}
     */
    public function process(ReflectionClass $reflection, array $data): array
    {
        $navigation = $this->getAttribute($reflection, Navigation::class);

        // Even without explicit Navigation attribute, 
        // we may want to generate basic navigation data
        $data['navigation'] = $navigation 
            ? $this->processNavigation($navigation, $data)
            : $this->generateDefaultNavigation($reflection, $data);

        return $data;
    }

    /**
     * Process explicit navigation attribute.
     * 
     * @return array<string, mixed>
     */
    protected function processNavigation(Navigation $navigation, array $data): array
    {
        $navData = $navigation->toArray();

        // Merge with zone navigation defaults
        $zoneConfig = $data['route']['zone_config'] ?? [];
        $zoneNav = $zoneConfig['navigation'] ?? [];

        // Apply zone defaults for group if not specified
        if ($navData['group'] === null && isset($zoneNav['group'])) {
            $navData['group'] = $zoneNav['group'];
            $navData['group_segments'] = array_filter(explode('.', $zoneNav['group']));
        }

        // Apply zone icon default if not specified
        if ($navData['icon'] === null && isset($zoneNav['icon'])) {
            $navData['icon'] = $zoneNav['icon'];
        }

        // Add route reference for link generation
        $navData['route_name'] = $data['route']['final_name'] ?? null;
        $navData['has_params'] = $data['route']['has_required_params'] ?? false;

        return $navData;
    }

    /**
     * Generate default navigation data when no attribute is present.
     * 
     * @return array<string, mixed>|null
     */
    protected function generateDefaultNavigation(ReflectionClass $reflection, array $data): ?array
    {
        // Skip if no route data (non-routable component)
        if (!isset($data['route'])) {
            return null;
        }

        // Generate a sensible label from class name
        $className = $this->getShortClassName($reflection);
        $label = $this->classNameToLabel($className);

        // Get zone defaults
        $zoneConfig = $data['route']['zone_config'] ?? [];
        $zoneNav = $zoneConfig['navigation'] ?? [];

        return [
            'label' => $label,
            'group' => $zoneNav['group'] ?? null,
            'group_segments' => isset($zoneNav['group']) 
                ? array_filter(explode('.', $zoneNav['group'])) 
                : [],
            'icon' => $zoneNav['icon'] ?? null,
            'sort' => 999, // Default to end
            'hidden' => true, // Auto-generated nav is hidden by default
            'badge' => null,
            'badge_color' => null,
            'parent' => null,
            'meta' => [],
            'route_name' => $data['route']['final_name'] ?? null,
            'has_params' => $data['route']['has_required_params'] ?? false,
            'auto_generated' => true,
        ];
    }

    /**
     * Convert class name to human-readable label.
     * UserDashboard -> User Dashboard
     */
    protected function classNameToLabel(string $className): string
    {
        // Remove common suffixes
        $className = preg_replace('/(Page|Component|View|Index|Show|Edit|Create|List)$/', '', $className);
        
        // Add spaces before uppercase letters
        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $className));
    }
}
