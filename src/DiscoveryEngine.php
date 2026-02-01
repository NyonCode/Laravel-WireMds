<?php

declare(strict_types=1);

namespace NyonCode\WireMds;

use NyonCode\WireMds\Attributes\WebRoute;
use NyonCode\WireMds\Contracts\Discoverable;
use NyonCode\WireMds\Contracts\Processor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Discovery Engine - Pipeline-based component scanner.
 *
 * Scans configured directories for Livewire components with discovery attributes,
 * processes them through a pipeline of processors, and generates a manifest.
 */
class DiscoveryEngine
{
    /**
     * Registered processors sorted by priority.
     *
     * @var array<Processor>
     */
    protected array $processors = [];

    /**
     * Whether processors have been sorted.
     */
    protected bool $processorsSorted = false;

    /**
     * Discovery timing for debugging.
     *
     * @var array<string, float>
     */
    protected array $timing = [];

    /**
     * Register a processor.
     */
    public function addProcessor(Processor $processor): self
    {
        $this->processors[] = $processor;
        $this->processorsSorted = false;
        return $this;
    }

    /**
     * Get all registered processors.
     *
     * @return array<Processor>
     */
    public function getProcessors(): array
    {
        $this->sortProcessors();
        return $this->processors;
    }

    /**
     * Discover all components and return manifest data.
     *
     * @return array<string, array<string, mixed>>
     */
    public function discover(): array
    {
        $startTime = microtime(true);
        $manifest = [];

        $paths = config('discovery.paths', [app_path('Livewire')]);
        $excludePatterns = config('discovery.exclude', []);

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $classes = $this->findClasses($path, $excludePatterns);

            foreach ($classes as $className) {
                $componentData = $this->processClass($className);

                if ($componentData !== null) {
                    $key = $componentData['route']['final_name'] ?? $className;
                    $manifest[$key] = $componentData;
                }
            }
        }

        $this->timing['total'] = microtime(true) - $startTime;

        return $manifest;
    }

    /**
     * Find all class names in given path.
     *
     * @param array<string> $excludePatterns
     * @return array<string>
     */
    protected function findClasses(string $path, array $excludePatterns): array
    {
        $finder = new Finder();
        $finder->files()
            ->in($path)
            ->name('*.php')
            ->notName('Abstract*.php')
            ->notName('Base*.php');

        foreach ($excludePatterns as $pattern) {
            $finder->notPath($pattern);
        }

        $classes = [];

        foreach ($finder as $file) {
            $className = $this->getClassFromFile($file->getRealPath());

            if ($className && class_exists($className)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * Extract class name from PHP file.
     */
    protected function getClassFromFile(string $filePath): ?string
    {
        $contents = File::get($filePath);

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $className = $matches[1];
            return $namespace ? "{$namespace}\\{$className}" : $className;
        }

        return null;
    }

    /**
     * Process a single class through the pipeline.
     *
     * @return array<string, mixed>|null
     */
    protected function processClass(string $className): ?array
    {
        try {
            $reflection = new ReflectionClass($className);

            // Skip abstract classes and interfaces
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                return null;
            }

            // Skip classes without WebRoute attribute
            if (empty($reflection->getAttributes(WebRoute::class))) {
                return null;
            }

            // Initialize data array
            $data = [];

            // Run through all processors
            foreach ($this->getProcessors() as $processor) {
                if ($processor->shouldProcess($reflection)) {
                    $data = $processor->process($reflection, $data);
                }
            }

            // Check if component implements Discoverable interface
            if ($reflection->implementsInterface(Discoverable::class)) {
                $meta = $className::discoveryMeta();
                $data['custom_meta'] = $meta;
            }

            // Add discovery metadata
            $data['_discovery'] = [
                'discovered_at' => now()->toIso8601String(),
                'processors' => array_map(
                    fn($p) => get_class($p),
                    $this->getProcessors()
                ),
            ];

            return $data;

        } catch (\Throwable $e) {
            if (config('discovery.debug')) {
                report($e);
            }
            return null;
        }
    }

    /**
     * Sort processors by priority.
     */
    protected function sortProcessors(): void
    {
        if ($this->processorsSorted) {
            return;
        }

        usort($this->processors, fn($a, $b) => $a->priority() <=> $b->priority());
        $this->processorsSorted = true;
    }

    /**
     * Get timing information for debugging.
     *
     * @return array<string, float>
     */
    public function getTiming(): array
    {
        return $this->timing;
    }
}
