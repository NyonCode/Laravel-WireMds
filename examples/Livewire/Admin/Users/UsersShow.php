<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use NyonCode\WireMds\Attributes\Access;
use NyonCode\WireMds\Attributes\Navigation;
use NyonCode\WireMds\Attributes\Seo;
use NyonCode\WireMds\Attributes\WebRoute;
use NyonCode\WireMds\Contracts\Discoverable;
use NyonCode\WireMds\Services\MetaRenderer;
use NyonCode\WireMds\Traits\WithDiscoveryAccess;
use Livewire\Component;

/**
 * Admin User Detail - Example Component
 * 
 * Demonstrates:
 * - URL parameters with regex constraints
 * - Dynamic SEO titles with placeholders
 * - Parent relationship for breadcrumbs
 * - Discoverable interface for custom meta
 */
#[WebRoute(
    uri: '/users/{user}',
    name: 'admin.users.show',
    zone: 'admin',
    where: ['user' => '[0-9]+']
)]
#[Navigation(
    label: 'User Detail',
    group: 'User Management',
    icon: 'user',
    sort: 21,
    hidden: true, // Hidden from nav (accessed via list)
    parent: 'admin.users.index'
)]
#[Access(
    permission: 'admin.users.view'
)]
#[Seo(
    title: 'User: {name}',
    description: 'View details for {name}',
    noindex: true // Don't index user detail pages
)]
class UsersShow extends Component implements Discoverable
{
    use WithDiscoveryAccess;

    public User $user;

    /**
     * Provide additional discovery metadata.
     */
    public static function discoveryMeta(): array
    {
        return [
            'model' => User::class,
            'parameter' => 'user',
            'supports_soft_deletes' => true,
        ];
    }

    public function mount(User $user): void
    {
        $this->user = $user;

        // Set dynamic SEO values
        app(MetaRenderer::class)->setMany([
            'title' => "User: {$user->name}",
            'description' => "View details for {$user->name}",
        ]);
    }

    public function render()
    {
        return view('livewire.admin.users.show')
            ->layout('layouts.admin');
    }
}
