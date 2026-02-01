<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Discovery Cache
    |--------------------------------------------------------------------------
    |
    | When enabled, the discovery engine will use the cached manifest instead
    | of scanning files with reflection. Always enable in production.
    |
    */
    'cache' => [
        'enabled' => env('DISCOVERY_CACHE_ENABLED', env('APP_ENV') === 'production'),
        'path' => base_path('bootstrap/cache/discovery.php'),
        'ttl' => null, // null = permanent until manually cleared
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Paths
    |--------------------------------------------------------------------------
    |
    | Directories to scan for Livewire components with discovery attributes.
    | Paths are relative to the base path.
    |
    */
    'paths' => [
        app_path('Livewire'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Patterns
    |--------------------------------------------------------------------------
    |
    | File patterns to exclude from scanning. Supports glob patterns.
    |
    */
    'exclude' => [
        '**/Abstract*.php',
        '**/Base*.php',
        '**/Concerns/**',
        '**/Traits/**',
    ],

    /*
    |--------------------------------------------------------------------------
    | Zone Definitions
    |--------------------------------------------------------------------------
    |
    | Zones define URL prefixes, default middleware, and default permissions.
    | The normalizer will automatically apply zone defaults to components.
    |
    */
    'zones' => [
        
        'admin' => [
            'prefix' => '/admin',
            'middleware' => ['web', 'auth', 'verified'],
            'default_permission' => 'admin.*',
            'default_role' => 'admin',
            'guard' => 'web',
            'layout' => 'layouts.admin',
            'domain' => null,
            'rate_limit' => null,
            'navigation' => [
                'group' => 'Administration',
                'icon' => 'cog',
            ],
        ],

        'customer' => [
            'prefix' => '/account',
            'middleware' => ['web', 'auth', 'verified'],
            'default_permission' => 'customer.*',
            'default_role' => null,
            'guard' => 'web',
            'layout' => 'layouts.customer',
            'domain' => null,
            'rate_limit' => '60,1',
            'navigation' => [
                'group' => 'Account',
                'icon' => 'user',
            ],
        ],

        'frontend' => [
            'prefix' => '',
            'middleware' => ['web'],
            'default_permission' => null,
            'default_role' => null,
            'guard' => null,
            'layout' => 'layouts.app',
            'domain' => null,
            'rate_limit' => null,
            'navigation' => [
                'group' => null,
                'icon' => null,
            ],
        ],

        'api' => [
            'prefix' => '/api',
            'middleware' => ['api', 'auth:sanctum'],
            'default_permission' => 'api.*',
            'default_role' => null,
            'guard' => 'sanctum',
            'layout' => null,
            'domain' => null,
            'rate_limit' => '60,1',
            'navigation' => [
                'group' => null,
                'icon' => null,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Zone
    |--------------------------------------------------------------------------
    |
    | The zone to use when no zone is specified in the WebRoute attribute.
    |
    */
    'default_zone' => 'frontend',

    /*
    |--------------------------------------------------------------------------
    | Route Naming Convention
    |--------------------------------------------------------------------------
    |
    | How to generate route names when not explicitly provided.
    | Options: 'class' (from class name), 'uri' (from URI segments)
    |
    */
    'route_naming' => 'class',

    /*
    |--------------------------------------------------------------------------
    | SEO Defaults
    |--------------------------------------------------------------------------
    |
    | Default SEO values applied when not specified in the Seo attribute.
    |
    */
    'seo' => [
        'title_suffix' => ' | ' . env('APP_NAME', 'Laravel'),
        'default_description' => env('APP_DESCRIPTION', ''),
        'default_og_image' => '/images/og-default.png',
        'twitter_site' => env('TWITTER_SITE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic sitemap generation.
    |
    */
    'sitemap' => [
        'enabled' => true,
        'path' => public_path('sitemap.xml'),
        'base_url' => env('APP_URL'),
        'include_last_modified' => true,
        'exclude_zones' => ['admin', 'customer', 'api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic breadcrumb generation.
    |
    */
    'breadcrumbs' => [
        'enabled' => true,
        'home_label' => 'Home',
        'home_route' => 'home',
        'separator' => '/',
        'use_translation' => false,
        'translation_prefix' => 'breadcrumbs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic navigation building.
    |
    */
    'navigation' => [
        'cache_key' => 'discovery.navigation',
        'cache_ttl' => 3600,
        'max_depth' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, shows detailed discovery information and timing.
    |
    */
    'debug' => env('DISCOVERY_DEBUG', false),

];
