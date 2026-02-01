<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Support\Discovery\Attributes\Access;
use App\Support\Discovery\Attributes\Navigation;
use App\Support\Discovery\Attributes\Seo;
use App\Support\Discovery\Attributes\WebRoute;
use App\Support\Discovery\Traits\WithDiscoveryAccess;
use Livewire\Component;

/**
 * Admin Dashboard - Example Component
 * 
 * Demonstrates all MDS attributes working together.
 */
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
#[Access(
    permission: 'admin.dashboard.view'
)]
#[Seo(
    title: 'Admin Dashboard',
    description: 'Overview of your administration panel'
)]
class Dashboard extends Component
{
    use WithDiscoveryAccess;

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.admin');
    }
}
