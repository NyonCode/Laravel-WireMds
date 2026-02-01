<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Processors;

use NyonCode\WireMds\Attributes\Seo;
use ReflectionClass;

/**
 * Processes Seo attributes and extracts SEO metadata.
 */
class SeoProcessor extends AbstractProcessor
{
    protected int $defaultPriority = 40;

    /**
     * {@inheritdoc}
     */
    public function process(ReflectionClass $reflection, array $data): array
    {
        $seo = $this->getAttribute($reflection, Seo::class);

        $data['seo'] = $seo 
            ? $this->processSeo($seo, $data)
            : $this->generateDefaultSeo($reflection, $data);

        return $data;
    }

    /**
     * Process explicit SEO attribute.
     * 
     * @return array<string, mixed>
     */
    protected function processSeo(Seo $seo, array $data): array
    {
        $seoData = $seo->toArray();

        // Apply global SEO config defaults
        $globalConfig = config('discovery.seo', []);

        // Apply title suffix if set
        if ($seoData['title'] && isset($globalConfig['title_suffix'])) {
            $seoData['full_title'] = $seoData['title'] . $globalConfig['title_suffix'];
        } else {
            $seoData['full_title'] = $seoData['title'];
        }

        // Apply default OG image
        if (!$seoData['og_image'] && isset($globalConfig['default_og_image'])) {
            $seoData['og_image'] = $globalConfig['default_og_image'];
        }

        // Check if should be in sitemap based on access restrictions
        $isPublic = $data['access']['is_public'] ?? true;
        $seoData['sitemap_eligible'] = $seoData['should_sitemap'] && $isPublic;

        return $seoData;
    }

    /**
     * Generate default SEO data.
     * 
     * @return array<string, mixed>
     */
    protected function generateDefaultSeo(ReflectionClass $reflection, array $data): array
    {
        $className = $this->getShortClassName($reflection);
        $label = $data['navigation']['label'] ?? $this->classNameToLabel($className);
        
        $globalConfig = config('discovery.seo', []);

        $title = $label;
        $fullTitle = $title . ($globalConfig['title_suffix'] ?? '');

        $isPublic = $data['access']['is_public'] ?? true;

        return [
            'title' => $title,
            'full_title' => $fullTitle,
            'description' => $globalConfig['default_description'] ?? null,
            'noindex' => !$isPublic, // Auto noindex non-public pages
            'nofollow' => false,
            'robots' => $isPublic ? 'index, follow' : 'noindex, follow',
            'sitemap_priority' => 0.5,
            'sitemap_frequency' => Seo::FREQUENCY_WEEKLY,
            'sitemap_include' => true,
            'should_sitemap' => $isPublic,
            'sitemap_eligible' => $isPublic,
            'canonical' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => $globalConfig['default_og_image'] ?? null,
            'og_type' => 'website',
            'twitter_card' => 'summary',
            'keywords' => [],
            'meta' => [],
            'auto_generated' => true,
        ];
    }

    /**
     * Convert class name to human-readable label.
     */
    protected function classNameToLabel(string $className): string
    {
        $className = preg_replace('/(Page|Component|View|Index|Show|Edit|Create|List)$/', '', $className);
        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $className));
    }
}
