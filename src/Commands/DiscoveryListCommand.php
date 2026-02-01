<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Commands;

use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Console\Command;

/**
 * List all discovered components.
 */
class DiscoveryListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discovery:list 
                            {--zone= : Filter by zone}
                            {--json : Output as JSON}
                            {--public : Show only public routes}
                            {--nav : Show only routes with navigation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all discovered components and their configuration';

    public function __construct(
        protected ManifestRepository $repository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $manifest = $this->repository->all();

        // Apply filters
        if ($zone = $this->option('zone')) {
            $manifest = array_filter(
                $manifest,
                fn($item) => ($item['route']['zone'] ?? '') === $zone
            );
        }

        if ($this->option('public')) {
            $manifest = array_filter(
                $manifest,
                fn($item) => $item['access']['is_public'] ?? true
            );
        }

        if ($this->option('nav')) {
            $manifest = array_filter(
                $manifest,
                fn($item) => isset($item['navigation']) && !($item['navigation']['hidden'] ?? false)
            );
        }

        if (empty($manifest)) {
            $this->warn('No components found matching the criteria.');
            return self::SUCCESS;
        }

        // Output
        if ($this->option('json')) {
            $this->line(json_encode($manifest, JSON_PRETTY_PRINT));
        } else {
            $this->displayTable($manifest);
        }

        $this->info('Total: ' . count($manifest) . ' components');

        return self::SUCCESS;
    }

    /**
     * Display manifest as table.
     * 
     * @param array<string, array<string, mixed>> $manifest
     */
    protected function displayTable(array $manifest): void
    {
        $rows = [];

        foreach ($manifest as $name => $item) {
            $middleware = $item['middleware'] ?? [];
            $middlewareStr = count($middleware) > 3 
                ? implode(', ', array_slice($middleware, 0, 3)) . '...'
                : implode(', ', $middleware);

            $rows[] = [
                $name,
                $item['route']['full_uri'] ?? 'N/A',
                $item['route']['zone'] ?? 'N/A',
                $item['component']['short_name'] ?? 'N/A',
                $item['access']['is_public'] ?? true ? '✓ Public' : '✗ Protected',
                $item['navigation']['label'] ?? '-',
                $middlewareStr,
            ];
        }

        $this->table(
            ['Route Name', 'URI', 'Zone', 'Component', 'Access', 'Nav Label', 'Middleware'],
            $rows
        );
    }
}
