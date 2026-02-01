<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Contracts;

/**
 * Contract for discoverable components.
 * 
 * Components implementing this interface can provide additional
 * metadata or modify their discovery data at runtime.
 */
interface Discoverable
{
    /**
     * Get additional discovery metadata.
     * 
     * This method is called during discovery to allow components
     * to provide runtime-generated metadata.
     * 
     * @return array<string, mixed>
     */
    public static function discoveryMeta(): array;
}
