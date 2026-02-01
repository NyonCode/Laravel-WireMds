<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Contracts;

/**
 * Contract for discovery pipeline processors.
 * 
 * Processors are responsible for extracting and transforming
 * specific types of metadata from component classes.
 */
interface Processor
{
    /**
     * Process a discovered class and extract relevant metadata.
     * 
     * @param \ReflectionClass $reflection The class reflection
     * @param array<string, mixed> $data Current discovery data (from previous processors)
     * @return array<string, mixed> Modified discovery data
     */
    public function process(\ReflectionClass $reflection, array $data): array;

    /**
     * Get the processor priority (lower = runs first).
     */
    public function priority(): int;

    /**
     * Check if this processor should run for the given class.
     */
    public function shouldProcess(\ReflectionClass $reflection): bool;
}
