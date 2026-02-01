<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Services;

use DOMDocument;
use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use SimpleXMLElement;

/**
 * Sitemap Generator Service
 * 
 * Generates XML sitemap from public routes discovered by the MDS framework.
 * Only includes routes that are:
 * - Public (no access restrictions)
 * - Not marked as noindex
 * - Configured for sitemap inclusion
 * - From zones not excluded in config
 */
class SitemapGenerator
{
    /**
     * XML namespace for sitemap.
     */
    protected const XMLNS = 'https://www.sitemaps.org/schemas/sitemap/0.9';

    public function __construct(
        protected ManifestRepository $repository,
    ) {}

    /**
     * Generate sitemap XML content.
     */
    public function generate(): string
    {
        $routes = $this->repository->getPublicRoutes();
        $baseUrl = rtrim(config('discovery.sitemap.base_url', config('app.url')), '/');

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset/>');
        $xml->addAttribute('xmlns', self::XMLNS);

        foreach ($routes as $name => $item) {
            $this->addUrlEntry($xml, $item, $baseUrl);
        }

        return $this->formatXml($xml);
    }

    /**
     * Generate and save sitemap to file.
     */
    public function save(?string $path = null): bool
    {
        $path = $path ?? config('discovery.sitemap.path', public_path('sitemap.xml'));

        $content = $this->generate();

        // Ensure directory exists
        $directory = dirname($path);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return (bool) File::put($path, $content);
    }

    /**
     * Add a URL entry to the sitemap.
     */
    protected function addUrlEntry(SimpleXMLElement $xml, array $item, string $baseUrl): void
    {
        // Skip routes with required parameters (can't generate static URLs)
        if ($item['route']['has_required_params'] ?? false) {
            return;
        }

        $url = $xml->addChild('url');
        
        // Location (full URL)
        $uri = $item['route']['full_uri'] ?? '/';
        $url->addChild('loc', $baseUrl . $uri);

        // Priority
        $priority = $item['seo']['sitemap_priority'] ?? 0.5;
        $url->addChild('priority', (string) $priority);

        // Change frequency
        $frequency = $item['seo']['sitemap_frequency'] ?? 'weekly';
        $url->addChild('changefreq', $frequency);

        // Last modified (if configured)
        if (config('discovery.sitemap.include_last_modified', true)) {
            $url->addChild('lastmod', now()->toW3cString());
        }
    }

    /**
     * Format XML with proper indentation.
     */
    protected function formatXml(SimpleXMLElement $xml): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Get routes that would be included in sitemap.
     * Useful for debugging.
     * 
     * @return array<string, array<string, mixed>>
     */
    public function getIncludedRoutes(): array
    {
        return array_filter(
            $this->repository->getPublicRoutes(),
            fn($item) => !($item['route']['has_required_params'] ?? false)
        );
    }

    /**
     * Get count of routes in sitemap.
     */
    public function count(): int
    {
        return count($this->getIncludedRoutes());
    }
}
