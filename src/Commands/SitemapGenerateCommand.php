<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Commands;

use NyonCode\WireMds\Services\SitemapGenerator;
use Illuminate\Console\Command;

/**
 * Generate XML sitemap from discovered public routes.
 */
class SitemapGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discovery:sitemap 
                            {--path= : Custom output path}
                            {--show : Display URLs instead of saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate XML sitemap from discovered public routes';

    public function __construct(
        protected SitemapGenerator $generator,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('show')) {
            return $this->showUrls();
        }

        $path = $this->option('path') ?? config('discovery.sitemap.path');

        if (!$path) {
            $this->error('Sitemap path not configured.');
            return self::FAILURE;
        }

        $this->info('Generating sitemap...');

        if ($this->generator->save($path)) {
            $count = $this->generator->count();
            $this->info("Sitemap generated with {$count} URLs: {$path}");
            return self::SUCCESS;
        }

        $this->error('Failed to generate sitemap.');
        return self::FAILURE;
    }

    /**
     * Display URLs that would be in sitemap.
     */
    protected function showUrls(): int
    {
        $routes = $this->generator->getIncludedRoutes();

        if (empty($routes)) {
            $this->warn('No routes eligible for sitemap.');
            return self::SUCCESS;
        }

        $rows = [];
        $baseUrl = config('discovery.sitemap.base_url', config('app.url'));

        foreach ($routes as $name => $item) {
            $rows[] = [
                $name,
                $baseUrl . ($item['route']['full_uri'] ?? '/'),
                $item['seo']['sitemap_priority'] ?? 0.5,
                $item['seo']['sitemap_frequency'] ?? 'weekly',
            ];
        }

        $this->table(
            ['Route Name', 'URL', 'Priority', 'Frequency'],
            $rows
        );

        $this->info('Total: ' . count($rows) . ' URLs');

        return self::SUCCESS;
    }
}
