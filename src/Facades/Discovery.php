<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Facades;

use NyonCode\WireMds\Cache\ManifestRepository;
use Illuminate\Support\Facades\Facade;

/**
 * Discovery Facade
 * 
 * @method static array all()
 * @method static array|null get(string $routeName)
 * @method static array|null findByUri(string $uri)
 * @method static array|null findByClass(string $className)
 * @method static array getByZone(string $zone)
 * @method static array getPublicRoutes()
 * @method static array getNavigationItems()
 * @method static array getNavigationForZone(string $zone)
 * @method static bool isCached()
 * @method static void clear()
 *
 * @see \NyonCode\WireMds\Cache\ManifestRepository
 */
class Discovery extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ManifestRepository::class;
    }
}
