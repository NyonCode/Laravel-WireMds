# Modular Discovery System (MDS) for Laravel

Enterprise-grade framework for automatic component registration and management in Laravel and Livewire 3.

> **Powered by [laravel-package-toolkit](https://github.com/NyonCode/laravel-package-toolkit)** - Uses modern package toolkit for streamlined package registration and management.

## Table of Contents

- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Attributes](#attributes)
- [Zones](#zones)
- [Cache](#cache)
- [SEO & Sitemap](#seo--sitemap)
- [Breadcrumbs](#breadcrumbs)
- [Navigation](#navigation)
- [Blade Components](#blade-components)
- [Commands](#commands)

## Installation

### Via Composer (as a package)

```bash
composer require your-vendor/mds-framework
```

### Manual Installation

#### 1. Install dependencies

```bash
composer require nyoncode/laravel-package-toolkit spatie/laravel-permission
```

#### 2. Copy files

```bash
cp -r src/* app/Support/Discovery/
cp config/discovery.php config/
cp -r resources/views/vendor/discovery resources/views/vendor/
```

#### 3. Register Service Provider

In `bootstrap/providers.php`:

```php
return [
    // ...
    App\Support\Discovery\DiscoveryServiceProvider::class,
];
```

### 4. Publish configuration (optional)

```bash
# Use install command (recommended)
php artisan discovery:install

# Or manually
php artisan vendor:publish --tag=discovery::config
php artisan vendor:publish --tag=discovery::views
```

## Basic Usage

### Livewire component with MDS

```php
<?php

namespace App\Livewire\Admin;

use App\Support\Discovery\Attributes\Access;
use App\Support\Discovery\Attributes\Navigation;
use App\Support\Discovery\Attributes\Seo;
use App\Support\Discovery\Attributes\WebRoute;
use App\Support\Discovery\Traits\WithDiscoveryAccess;
use Livewire\Component;

#[WebRoute(
    uri: '/dashboard',
    name: 'admin.dashboard',
    zone: 'admin'
)]
#[Navigation(
    label: 'Dashboard',
    icon: 'home',
    sort: 10
)]
#[Access(permission: 'admin.dashboard.view')]
#[Seo(
    title: 'Admin Dashboard',
    description: 'Overview of administration'
)]
class Dashboard extends Component
{
    use WithDiscoveryAccess;

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
```

## Attributes

### #[WebRoute]

Defines component routing.

```php
#[WebRoute(
    uri: '/products/{id}/{slug?}',  // URI with parameters
    name: 'products.show',          // Route name (optional)
    zone: 'frontend',               // Zone (frontend, admin, customer)
    middleware: ['verified'],       // Additional middleware
    where: ['id' => '[0-9]+'],     // Regex constraints
    methods: ['GET'],              // HTTP methods
    domain: null                   // Domain constraint
)]
```

### #[Navigation]

Metadata for UI navigation.

```php
#[Navigation(
    label: 'Users',                    // Display text
    group: 'Settings.Users',           // Hierarchical group (dot-separated)
    icon: 'users',                     // Icon (Heroicons)
    sort: 20,                          // Order (lower = higher)
    hidden: false,                     // Hide from navigation
    badge: 'NEW',                      // Badge text
    badgeColor: 'bg-green-500',        // Badge color
    parent: 'admin.settings.index'     // Parent route for breadcrumbs
)]
```

### #[Access]

Access control (Spatie Permission integration).

```php
#[Access(
    permission: 'users.view',           // Required permission
    // or
    permission: ['users.view', 'admin.*'], // Multiple permissions (supports wildcards)
    roles: ['admin', 'manager'],        // Required roles
    require: 'any',                     // 'any' or 'all'
    guard: 'web',                       // Authentication guard
    authenticated: true,                // Requires authentication
    redirectTo: 'login',               // Redirect on unauthorized access
    httpCode: 403                      // HTTP code on denial
)]
```

### #[Seo]

SEO and sitemap configuration.

```php
#[Seo(
    title: 'Product: {name}',           // Title (supports placeholders)
    description: 'View {name} details', // Description
    noindex: false,                     // Disable indexing
    nofollow: false,                    // Disable link following
    sitemap_priority: 0.8,              // Sitemap priority (0.0-1.0)
    sitemap_frequency: 'weekly',        // Update frequency
    canonical: '/products/{slug}',      // Canonical URL
    og_image: '/images/product.png',    // Open Graph image
    og_type: 'product',                 // Open Graph type
    twitter_card: 'summary_large_image' // Twitter Card type
)]
```

## Zones

Zones define URL prefixes, default middleware, and permissions.

### Configuration (`config/discovery.php`)

```php
'zones' => [
    'admin' => [
        'prefix' => '/admin',
        'middleware' => ['web', 'auth', 'verified'],
        'default_permission' => 'admin.*',
        'default_role' => 'admin',
        'layout' => 'layouts.admin',
    ],
    'customer' => [
        'prefix' => '/account',
        'middleware' => ['web', 'auth'],
        'default_permission' => 'customer.*',
        'layout' => 'layouts.customer',
    ],
    'frontend' => [
        'prefix' => '',
        'middleware' => ['web'],
        'default_permission' => null,  // Public
        'layout' => 'layouts.app',
    ],
],
```

### Automatic Normalization

If a component in the `admin` zone doesn't have `#[Access]` defined, it automatically gets:
- `permission: 'admin.*'`
- `authenticated: true`

## Cache

### Generate cache (production)

```bash
php artisan discovery:cache
```

### Clear cache

```bash
php artisan discovery:clear
```

### Integration with `optimize`

Cache is automatically generated when running:

```bash
php artisan optimize
```

And cleared when running:

```bash
php artisan optimize:clear
```

## SEO & Sitemap

### Meta tags in Blade

```blade
<head>
    <x-discovery::meta-tags />
</head>
```

### Dynamic settings in component

```php
use App\Support\Discovery\Facades\Seo;

public function mount(Product $product)
{
    Seo::set('title', "Product: {$product->name}");
    Seo::set('description', $product->description);
    Seo::set('og_image', $product->image_url);
}
```

### Generate sitemap

```bash
# Generate sitemap.xml
php artisan discovery:sitemap

# Show URLs without generating
php artisan discovery:sitemap --show
```

## Breadcrumbs

### Basic usage

```blade
<x-discovery::breadcrumbs />
```

### With dynamic parameters

```blade
<x-discovery::breadcrumbs :parameters="['name' => $product->name]" />
```

### Register custom resolver

```php
// In AppServiceProvider
use App\Support\Discovery\Services\BreadcrumbService;

public function boot()
{
    app(BreadcrumbService::class)->registerResolver('products.show', function ($route, $params) {
        return Product::find($params['product'])?->name ?? 'Product';
    });
}
```

## Navigation

### In Blade template

```blade
<x-discovery::navigation zone="admin" />
```

### With custom classes

```blade
<x-discovery::navigation 
    zone="admin"
    class="space-y-2"
    item-class="px-4 py-2"
    active-class="bg-blue-500 text-white"
    :show-icons="true"
    :show-badges="true"
/>
```

### Programmatically

```php
use App\Support\Discovery\Services\NavigationBuilder;

$navigation = app(NavigationBuilder::class)->forZone('admin');
$flatNav = app(NavigationBuilder::class)->flatForZone('admin');
```

## Blade Components

### `<x-discovery::meta-tags />`
Renders all SEO meta tags.

### `<x-discovery::breadcrumbs />`
Renders breadcrumb navigation.

**Props:**
- `parameters` - Array for placeholder replacement
- `separator` - Separator (default: `/`)
- `class` - CSS classes for container

### `<x-discovery::navigation />`
Renders hierarchical navigation.

**Props:**
- `zone` - Zone (required)
- `class` - CSS classes
- `show-icons` - Show icons (default: true)
- `show-badges` - Show badges (default: true)

## Commands

| Command | Description |
|---------|-------------|
| `discovery:cache` | Generates cache manifest |
| `discovery:clear` | Clears cache |
| `discovery:list` | Shows all components |
| `discovery:sitemap` | Generates sitemap.xml |

### `discovery:list` options

```bash
# Filter by zone
php artisan discovery:list --zone=admin

# Public routes only
php artisan discovery:list --public

# Navigation items only
php artisan discovery:list --nav

# JSON output
php artisan discovery:list --json
```

## WithDiscoveryAccess Trait

Trait automatically verifies permissions in `mount()` method.

```php
use App\Support\Discovery\Traits\WithDiscoveryAccess;

class MyComponent extends Component
{
    use WithDiscoveryAccess;
    
    // Permissions are checked automatically before mount()
}
```

## Discoverable Interface

For components that need to provide additional metadata:

```php
use App\Support\Discovery\Contracts\Discoverable;

class MyComponent extends Component implements Discoverable
{
    public static function discoveryMeta(): array
    {
        return [
            'model' => User::class,
            'supports_export' => true,
        ];
    }
}
```

## Facades

### Discovery

```php
use App\Support\Discovery\Facades\Discovery;

$all = Discovery::all();
$component = Discovery::get('admin.dashboard');
$byUri = Discovery::findByUri('/admin/dashboard');
$adminRoutes = Discovery::getByZone('admin');
$publicRoutes = Discovery::getPublicRoutes();
```

### Seo

```php
use App\Support\Discovery\Facades\Seo;

Seo::set('title', 'Custom Title');
Seo::setMany(['title' => '...', 'description' => '...']);
$data = Seo::getSeoData();
$html = Seo::render();
```

## Best Practices

1. **Always use cache in production** - Reflection is slow
2. **Define zones properly** - Automatic normalization saves work
3. **Use `WithDiscoveryAccess`** - Consistent permission checking
4. **Use wildcards carefully** - `admin.*` is powerful but dangerous
5. **SEO for public pages** - Sitemap contains only public routes

## License

MIT
