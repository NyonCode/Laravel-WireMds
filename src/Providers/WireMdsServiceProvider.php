<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Providers;

use NyonCode\WireMds\Cache\ManifestRepository;
use NyonCode\WireMds\Commands\DiscoveryCacheCommand;
use NyonCode\WireMds\Commands\DiscoveryClearCommand;
use NyonCode\WireMds\Commands\DiscoveryListCommand;
use NyonCode\WireMds\Commands\SitemapGenerateCommand;
use NyonCode\WireMds\Contracts\Processor;
use NyonCode\WireMds\DiscoveryEngine;
use NyonCode\WireMds\Processors\AccessProcessor;
use NyonCode\WireMds\Processors\NavigationProcessor;
use NyonCode\WireMds\Processors\RouteProcessor;
use NyonCode\WireMds\Processors\SeoProcessor;
use NyonCode\WireMds\Services\BreadcrumbService;
use NyonCode\WireMds\Services\MetaRenderer;
use NyonCode\WireMds\Services\NavigationBuilder;
use NyonCode\WireMds\Services\RouteRegistrar;
use NyonCode\WireMds\Services\SitemapGenerator;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Blade;
use NyonCode\LaravelPackageToolkit\Commands\InstallCommand;
use NyonCode\LaravelPackageToolkit\Contracts\HasAbout;
use NyonCode\LaravelPackageToolkit\Contracts\Packable;
use NyonCode\LaravelPackageToolkit\Packager;
use NyonCode\LaravelPackageToolkit\PackageServiceProvider;


/**
 * Modular Discovery System Service Provider.
 *
 * Uses laravel-package-toolkit for streamlined package registration.
 *
 * @see https://github.com/NyonCode/laravel-package-toolkit
 */
class WireMdsServiceProvider extends PackageServiceProvider implements Packable, HasAbout
{
    /**
     * Configure the package using Packager.
     *
     * @throws Exception
     */
    public function configure(Packager $packager): void
    {
        $packager
            ->name('Modular Discovery System')
            ->hasShortName('discovery')
            ->hasConfig()
            ->hasViews()
            ->hasCommands([
                DiscoveryCacheCommand::class,
                DiscoveryClearCommand::class,
                DiscoveryListCommand::class,
                SitemapGenerateCommand::class,
            ])
            ->hasAbout()
            ->hasVersion('1.0.0')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfig()
                    ->publishViews()
                    ->afterInstallation(function ($cmd) {
                        $cmd->info('Running discovery:cache...');
                        $cmd->call('discovery:cache');
                        $cmd->info('MDS installed successfully!');
                    })
                    ->askToStarRepoOnGitHub('https://github.com/your-org/mds-framework');
            });
    }

    /**
     * Custom data for artisan about command.
     */
    public function aboutData(): array
    {
        $manifest = $this->app->make(ManifestRepository::class);
        $stats = $manifest->getStats();

        return [
            'Components' => $stats['total'] ?? 0,
            'Cached' => $manifest->isCached() ? 'Yes' : 'No',
            'Zones' => implode(', ', array_keys(config('discovery.zones', []))),
        ];
    }

    /**
     * Register package services before registration.
     */
    public function registeringPackage(): void
    {
        $this->registerProcessors();
        $this->registerEngine();
        $this->registerManifest();
        $this->registerServices();
        $this->registerFacades();
    }

    /**
     * Boot package after registration.
     */
    public function bootingPackage(): void
    {
        $this->registerBladeComponents();
    }

    /**
     * After package is fully booted.
     */
    public function bootedPackage(): void
    {
        $this->registerRoutes();
    }

    /**
     * Register ordered processors pipeline.
     */
    protected function registerProcessors(): void
    {
        // Register individual processors
        $this->app->singleton(RouteProcessor::class);
        $this->app->singleton(NavigationProcessor::class);
        $this->app->singleton(AccessProcessor::class);
        $this->app->singleton(SeoProcessor::class);

        // Register processor pipeline (ordered)
        $this->app->tag([
            RouteProcessor::class,
            NavigationProcessor::class,
            AccessProcessor::class,
            SeoProcessor::class,
        ], Processor::class);
    }

    /**
     * Register the discovery engine.
     */
    protected function registerEngine(): void
    {
        $this->app->singleton(DiscoveryEngine::class, function ($app) {
            $engine = new DiscoveryEngine();

            // Add processors in order
            $processors = collect($app->tagged(Processor::class))
                ->sortBy(fn (Processor $p) => $p->priority())
                ->values();

            foreach ($processors as $processor) {
                $engine->addProcessor($processor);
            }

            return $engine;
        });
    }

    /**
     * Register the manifest repository.
     */
    protected function registerManifest(): void
    {
        $this->app->singleton(ManifestRepository::class, function ($app) {
            return new ManifestRepository(
                $app->bootstrapPath('cache/discovery.php'),
                $app->make(DiscoveryEngine::class)
            );
        });
    }

    /**
     * Register all services.
     */
    protected function registerServices(): void
    {
        $this->app->singleton(RouteRegistrar::class, function ($app) {
            return new RouteRegistrar(
                $app->make(ManifestRepository::class)
            );
        });

        $this->app->singleton(NavigationBuilder::class, function ($app) {
            return new NavigationBuilder(
                $app->make(ManifestRepository::class)
            );
        });

        $this->app->singleton(BreadcrumbService::class, function ($app) {
            return new BreadcrumbService(
                $app->make(ManifestRepository::class)
            );
        });

        $this->app->singleton(MetaRenderer::class, function ($app) {
            return new MetaRenderer(
                $app->make(ManifestRepository::class)
            );
        });

        $this->app->singleton(SitemapGenerator::class, function ($app) {
            return new SitemapGenerator(
                $app->make(ManifestRepository::class)
            );
        });
    }

    /**
     * Register facades.
     */
    protected function registerFacades(): void
    {
        $this->app->alias(ManifestRepository::class, 'discovery');
        $this->app->alias(MetaRenderer::class, 'discovery.seo');
    }

    /**
     * Register Blade components.
     */
    protected function registerBladeComponents(): void
    {
        Blade::componentNamespace('App\\Support\\Discovery\\View\\Components', 'discovery');

        // Anonymous components from views
        Blade::anonymousComponentPath(
            resource_path('views/vendor/discovery/components'),
            'discovery'
        );
    }

    /**
     * Register routes from manifest after app boot.
     */
    protected function registerRoutes(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        if (!$app->routesAreCached()) {
            $this->app->make(RouteRegistrar::class)->register();
        }
    }
}