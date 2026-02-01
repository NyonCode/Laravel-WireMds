<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Clear the discovery manifest cache.
 */
class DiscoveryClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discovery:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cached discovery manifest';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cachePath = config('discovery.cache.path');

        if (!$cachePath) {
            $this->warn('Discovery cache path not configured.');
            return self::SUCCESS;
        }

        if (!File::exists($cachePath)) {
            $this->info('Discovery cache does not exist.');
            return self::SUCCESS;
        }

        File::delete($cachePath);

        $this->info('Discovery cache cleared successfully.');

        return self::SUCCESS;
    }
}
