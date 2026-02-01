<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Support\Discovery\Attributes\Access;
use App\Support\Discovery\Attributes\Navigation;
use App\Support\Discovery\Attributes\Seo;
use App\Support\Discovery\Attributes\WebRoute;
use App\Support\Discovery\Traits\WithDiscoveryAccess;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin Users List - Example Component
 * 
 * Demonstrates nested routes and group navigation.
 */
#[WebRoute(
    uri: '/users',
    name: 'admin.users.index',
    zone: 'admin'
)]
#[Navigation(
    label: 'All Users',
    group: 'User Management',
    icon: 'users',
    sort: 20
)]
#[Access(
    permission: 'admin.users.*'
)]
#[Seo(
    title: 'Users Management',
    description: 'Manage all users in the system',
    sitemap_include: false
)]
class UsersIndex extends Component
{
    use WithDiscoveryAccess;
    use WithPagination;

    public string $search = '';

    public function render()
    {
        return view('livewire.admin.users.index')
            ->layout('layouts.admin');
    }
}
