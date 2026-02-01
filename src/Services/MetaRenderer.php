<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Services;

use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;

/**
 * Meta Renderer Service
 * 
 * Renders SEO meta tags from discovery manifest data.
 * Supports Open Graph, Twitter Cards, and standard meta tags.
 */
class MetaRenderer
{
    /**
     * Current override values.
     * 
     * @var array<string, mixed>
     */
    protected array $overrides = [];

    public function __construct(
        protected ManifestRepository $repository,
    ) {}

    /**
     * Override a specific meta value for the current request.
     * 
     * @param string $key Meta key (title, description, og_image, etc.)
     * @param mixed $value Override value
     */
    public function set(string $key, mixed $value): self
    {
        $this->overrides[$key] = $value;
        return $this;
    }

    /**
     * Bulk set overrides.
     * 
     * @param array<string, mixed> $values
     */
    public function setMany(array $values): self
    {
        $this->overrides = array_merge($this->overrides, $values);
        return $this;
    }

    /**
     * Get the SEO data for the current route.
     * 
     * @return array<string, mixed>
     */
    public function getSeoData(): array
    {
        $routeName = Route::currentRouteName();
        $component = $routeName ? $this->repository->get($routeName) : null;
        $seo = $component['seo'] ?? $this->getDefaults();

        return array_merge($seo, $this->overrides);
    }

    /**
     * Render all meta tags as HTML.
     */
    public function render(): HtmlString
    {
        $seo = $this->getSeoData();
        $html = [];

        // Title
        $title = $seo['full_title'] ?? $seo['title'] ?? config('app.name');
        $html[] = "<title>{$this->escape($title)}</title>";

        // Description
        if ($description = $seo['description'] ?? null) {
            $html[] = "<meta name=\"description\" content=\"{$this->escape($description)}\">";
        }

        // Robots
        $html[] = "<meta name=\"robots\" content=\"{$this->escape($seo['robots'] ?? 'index, follow')}\">";

        // Canonical
        if ($canonical = $seo['canonical'] ?? null) {
            $canonicalUrl = str_starts_with($canonical, 'http') 
                ? $canonical 
                : url($canonical);
            $html[] = "<link rel=\"canonical\" href=\"{$this->escape($canonicalUrl)}\">";
        }

        // Keywords (if any)
        $keywords = $seo['keywords'] ?? [];
        if (!empty($keywords)) {
            $html[] = "<meta name=\"keywords\" content=\"{$this->escape(implode(', ', $keywords))}\">";
        }

        // Open Graph
        $html = array_merge($html, $this->renderOpenGraph($seo));

        // Twitter Card
        $html = array_merge($html, $this->renderTwitterCard($seo));

        // Additional meta tags
        foreach ($seo['meta'] ?? [] as $name => $content) {
            $html[] = "<meta name=\"{$this->escape($name)}\" content=\"{$this->escape($content)}\">";
        }

        return new HtmlString(implode("\n    ", $html));
    }

    /**
     * Render just the title tag.
     */
    public function renderTitle(): HtmlString
    {
        $seo = $this->getSeoData();
        $title = $seo['full_title'] ?? $seo['title'] ?? config('app.name');

        return new HtmlString("<title>{$this->escape($title)}</title>");
    }

    /**
     * Get the current page title (without HTML).
     */
    public function getTitle(): string
    {
        $seo = $this->getSeoData();
        return $seo['full_title'] ?? $seo['title'] ?? config('app.name');
    }

    /**
     * Render Open Graph tags.
     * 
     * @param array<string, mixed> $seo
     * @return array<string>
     */
    protected function renderOpenGraph(array $seo): array
    {
        $html = [];

        $ogTitle = $seo['og_title'] ?? $seo['title'] ?? null;
        if ($ogTitle) {
            $html[] = "<meta property=\"og:title\" content=\"{$this->escape($ogTitle)}\">";
        }

        $ogDescription = $seo['og_description'] ?? $seo['description'] ?? null;
        if ($ogDescription) {
            $html[] = "<meta property=\"og:description\" content=\"{$this->escape($ogDescription)}\">";
        }

        if ($ogImage = $seo['og_image'] ?? null) {
            $imageUrl = str_starts_with($ogImage, 'http') ? $ogImage : url($ogImage);
            $html[] = "<meta property=\"og:image\" content=\"{$this->escape($imageUrl)}\">";
        }

        if ($ogType = $seo['og_type'] ?? null) {
            $html[] = "<meta property=\"og:type\" content=\"{$this->escape($ogType)}\">";
        }

        // Add URL
        $html[] = "<meta property=\"og:url\" content=\"{$this->escape(url()->current())}\">";

        // Add site name
        $html[] = "<meta property=\"og:site_name\" content=\"{$this->escape(config('app.name'))}\">";

        return $html;
    }

    /**
     * Render Twitter Card tags.
     * 
     * @param array<string, mixed> $seo
     * @return array<string>
     */
    protected function renderTwitterCard(array $seo): array
    {
        $html = [];

        $cardType = $seo['twitter_card'] ?? 'summary';
        $html[] = "<meta name=\"twitter:card\" content=\"{$this->escape($cardType)}\">";

        // Twitter site handle
        if ($twitterSite = config('discovery.seo.twitter_site')) {
            $html[] = "<meta name=\"twitter:site\" content=\"{$this->escape($twitterSite)}\">";
        }

        $title = $seo['og_title'] ?? $seo['title'] ?? null;
        if ($title) {
            $html[] = "<meta name=\"twitter:title\" content=\"{$this->escape($title)}\">";
        }

        $description = $seo['og_description'] ?? $seo['description'] ?? null;
        if ($description) {
            $html[] = "<meta name=\"twitter:description\" content=\"{$this->escape($description)}\">";
        }

        if ($image = $seo['og_image'] ?? null) {
            $imageUrl = str_starts_with($image, 'http') ? $image : url($image);
            $html[] = "<meta name=\"twitter:image\" content=\"{$this->escape($imageUrl)}\">";
        }

        return $html;
    }

    /**
     * Get default SEO values.
     * 
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'title' => config('app.name'),
            'full_title' => config('app.name'),
            'description' => config('discovery.seo.default_description'),
            'robots' => 'index, follow',
            'og_image' => config('discovery.seo.default_og_image'),
            'og_type' => 'website',
            'twitter_card' => 'summary',
            'keywords' => [],
            'meta' => [],
        ];
    }

    /**
     * Escape HTML entities.
     */
    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Clear all overrides.
     */
    public function clear(): self
    {
        $this->overrides = [];
        return $this;
    }
}
