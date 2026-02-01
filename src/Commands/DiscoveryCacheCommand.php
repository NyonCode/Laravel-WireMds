<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Commands;

use NyonCode\WireMds\DiscoveryEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Cache the discovery manifest for production.
 * 
 * This command scans all components and generates a static PHP
 * manifest file, eliminating the need for runtime reflection.
 */
class DiscoveryCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discovery:cache 
                            {--force : Force regeneration even if cache exists}
                            {--show : Display the generated manifest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache the discovery manifest for production';

    public function __construct(
        protected DiscoveryEngine $engine,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cachePath = config('discovery.cache.path');

        if (!$cachePath) {
            $this->error('Discovery cache path not configured.');
            return self::FAILURE;
        }

        // Check if cache exists and force flag not set
        if (File::exists($cachePath) && !$this->option('force')) {
            if (!$this->confirm('Cache file already exists. Regenerate?', true)) {
                $this->info('Cache generation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('Discovering components...');

        $startTime = microtime(true);
        $manifest = $this->engine->discover();
        $discoveryTime = round((microtime(true) - $startTime) * 1000, 2);

        $count = count($manifest);
        $this->info("Found {$count} components in {$discoveryTime}ms");

        // Generate PHP manifest file
        $content = $this->generateManifestFile($manifest);

        // Ensure directory exists
        $directory = dirname($cachePath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Write cache file
        File::put($cachePath, $content);

        $this->info("Discovery manifest cached to: {$cachePath}");

        // Show manifest if requested
        if ($this->option('show')) {
            $this->newLine();
            $this->displayManifest($manifest);
        }

        // Display summary table
        $this->displaySummary($manifest);

        return self::SUCCESS;
    }

    /**
     * Generate the PHP manifest file content.
     * 
     * @param array<string, array<string, mixed>> $manifest
     */
    protected function generateManifestFile(array $manifest): string
    {
        $exported = var_export($manifest, true);
        
        // Clean up the export for better readability
        $exported = preg_replace('/array \(/', '[', $exported);
        $exported = preg_replace('/\)$/', ']', $exported);
        $exported = preg_replace('/\)(\s*),/', ']$1,', $exported);
        $exported = preg_replace('/\)(\s*)\]/', ']$1]', $exported);

        $date = now()->toIso8601String();

        return <<<PHP
<?php

declare(strict_types=1);

/**
 * Discovery Manifest - Auto-generated
 * 
 * Generated: {$date}
 * Components: {count($manifest)}
 * 
 * DO NOT EDIT MANUALLY
 * Run `php artisan discovery:cache` to regenerate.
 */

return {$exported};
PHP;
    }

    /**
     * Display manifest in table format.
     * 
     * @param array<string, array<string, mixed>> $manifest
     */
    protected function displayManifest(array $manifest): void
    {
        $rows = [];

        foreach ($manifest as $name => $item) {
            $rows[] = [
                'name' => $name,
                'uri' => $item['route']['full_uri'] ?? 'N/A',
                'zone' => $item['route']['zone'] ?? 'N/A',
                'access' => $item['access']['is_public'] ?? true ? 'Public' : 'Protected',
                'nav' => isset($item['navigation']) && !($item['navigation']['hidden'] ?? false) 
                    ? 'Yes' : 'No',
            ];
        }

        $this->table(
            ['Route Name', 'URI', 'Zone', 'Access', 'Navigation'],
            $rows
        );
    }

    /**
     * Display summary statistics.
     * 
     * @param array<string, array<string, mixed>> $manifest
     */
    protected function displaySummary(array $manifest): void
    {
        $zones = [];
        $publicCount = 0;
        $protectedCount = 0;
        $navCount = 0;
        $sitemapCount = 0;

        foreach ($manifest as $item) {
            $zone = $item['route']['zone'] ?? 'frontend';
            $zones[$zone] = ($zones[$zone] ?? 0) + 1;

            if ($item['access']['is_public'] ?? true) {
                $publicCount++;
            } else {
                $protectedCount++;
            }

            if (isset($item['navigation']) && !($item['navigation']['hidden'] ?? false)) {
                $navCount++;
            }

            if ($item['seo']['sitemap_eligible'] ?? false) {
                $sitemapCount++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Components', count($manifest)],
                ['Public Routes', $publicCount],
                ['Protected Routes', $protectedCount],
                ['Navigation Items', $navCount],
                ['Sitemap Entries', $sitemapCount],
            ]
        );

        $this->newLine();
        $this->info('By Zone:');
        
        $zoneRows = array_map(
            fn($zone, $count) => [$zone, $count],
            array_keys($zones),
            array_values($zones)
        );

        $this->table(['Zone', 'Components'], $zoneRows);
    }
}
