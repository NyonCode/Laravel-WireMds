<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use NyonCode\WireMds\Attributes\Access;
use NyonCode\WireMds\Attributes\Navigation;
use NyonCode\WireMds\Attributes\Seo;
use NyonCode\WireMds\Attributes\WebRoute;
use NyonCode\WireMds\Traits\WithDiscoveryAccess;
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
