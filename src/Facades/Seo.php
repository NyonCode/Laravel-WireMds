<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Facades;

use NyonCode\WireMds\Services\MetaRenderer;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;

/**
 * Seo Facade
 * 
 * @method static MetaRenderer set(string $key, mixed $value)
 * @method static MetaRenderer setMany(array $values)
 * @method static array getSeoData()
 * @method static HtmlString render()
 * @method static HtmlString renderTitle()
 * @method static string getTitle()
 * @method static MetaRenderer clear()
 *
 * @see \NyonCode\WireMds\Services\MetaRenderer
 */
class Seo extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return MetaRenderer::class;
    }
}
