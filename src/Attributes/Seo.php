<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Attributes;

use Attribute;

/**
 * Defines SEO metadata for pages.
 * 
 * Provides configuration for meta tags, sitemap generation,
 * and social media sharing (Open Graph, Twitter Cards).
 *
 * @example Basic usage:
 * #[Seo(title: 'Dashboard', description: 'Your personal dashboard')]
 * 
 * @example Full configuration:
 * #[Seo(
 *     title: 'Products - {name}',
 *     description: 'View details for {name}',
 *     sitemap_priority: 0.8,
 *     sitemap_frequency: 'weekly',
 *     canonical: '/products/{slug}'
 * )]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Seo
{
    public const FREQUENCY_ALWAYS = 'always';
    public const FREQUENCY_HOURLY = 'hourly';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_YEARLY = 'yearly';
    public const FREQUENCY_NEVER = 'never';

    /**
     * @param string|null $title Page title (supports placeholders like {name})
     * @param string|null $description Meta description (supports placeholders)
     * @param bool $noindex Prevent search engine indexing
     * @param bool $nofollow Prevent following links
     * @param float $sitemap_priority Priority in sitemap (0.0 - 1.0)
     * @param string $sitemap_frequency Update frequency for sitemap
     * @param bool $sitemap_include Include in sitemap (auto-excludes if noindex or restricted)
     * @param string|null $canonical Canonical URL (relative or absolute)
     * @param string|null $og_title Open Graph title (falls back to title)
     * @param string|null $og_description Open Graph description
     * @param string|null $og_image Open Graph image URL
     * @param string|null $og_type Open Graph type (website, article, etc.)
     * @param string|null $twitter_card Twitter card type (summary, summary_large_image)
     * @param array<string> $keywords Meta keywords (deprecated but sometimes used)
     * @param array<string, string> $meta Additional meta tags
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public bool $noindex = false,
        public bool $nofollow = false,
        public float $sitemap_priority = 0.5,
        public string $sitemap_frequency = self::FREQUENCY_WEEKLY,
        public bool $sitemap_include = true,
        public ?string $canonical = null,
        public ?string $og_title = null,
        public ?string $og_description = null,
        public ?string $og_image = null,
        public ?string $og_type = 'website',
        public ?string $twitter_card = 'summary',
        public array $keywords = [],
        public array $meta = [],
    ) {}

    /**
     * Get robots meta tag content.
     */
    public function getRobotsContent(): string
    {
        $directives = [];

        $directives[] = $this->noindex ? 'noindex' : 'index';
        $directives[] = $this->nofollow ? 'nofollow' : 'follow';

        return implode(', ', $directives);
    }

    /**
     * Check if page should be in sitemap.
     */
    public function shouldIncludeInSitemap(): bool
    {
        return $this->sitemap_include && !$this->noindex;
    }

    /**
     * Get Open Graph title with fallback.
     */
    public function getOgTitle(): ?string
    {
        return $this->og_title ?? $this->title;
    }

    /**
     * Get Open Graph description with fallback.
     */
    public function getOgDescription(): ?string
    {
        return $this->og_description ?? $this->description;
    }

    /**
     * Process placeholders in a string.
     * 
     * @param string|null $template
     * @param array<string, string> $replacements
     */
    public function processPlaceholders(?string $template, array $replacements): ?string
    {
        if ($template === null) {
            return null;
        }

        $patterns = [];
        $values = [];

        foreach ($replacements as $key => $value) {
            $patterns[] = '/\{' . preg_quote($key, '/') . '\}/';
            $values[] = $value;
        }

        return preg_replace($patterns, $values, $template);
    }

    /**
     * Convert to array for manifest serialization.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'noindex' => $this->noindex,
            'nofollow' => $this->nofollow,
            'robots' => $this->getRobotsContent(),
            'sitemap_priority' => $this->sitemap_priority,
            'sitemap_frequency' => $this->sitemap_frequency,
            'sitemap_include' => $this->sitemap_include,
            'should_sitemap' => $this->shouldIncludeInSitemap(),
            'canonical' => $this->canonical,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,
            'og_type' => $this->og_type,
            'twitter_card' => $this->twitter_card,
            'keywords' => $this->keywords,
            'meta' => $this->meta,
        ];
    }
}
