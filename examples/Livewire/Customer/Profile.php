<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Support\Discovery\Attributes\Access;
use App\Support\Discovery\Attributes\Navigation;
use App\Support\Discovery\Attributes\Seo;
use App\Support\Discovery\Attributes\WebRoute;
use App\Support\Discovery\Traits\WithDiscoveryAccess;
use Livewire\Component;

/**
 * Customer Profile - Example Component
 * 
 * Demonstrates customer zone with authentication but no special permissions.
 */
#[WebRoute(
    uri: '/profile',
    name: 'customer.profile',
    zone: 'customer'
)]
#[Navigation(
    label: 'My Profile',
    icon: 'user',
    sort: 10
)]
#[Access(
    authenticated: true
    // No specific permission - zone defaults apply
)]
#[Seo(
    title: 'My Profile',
    description: 'Manage your account settings',
    noindex: true // User profiles shouldn't be indexed
)]
class Profile extends Component
{
    use WithDiscoveryAccess;

    public function render()
    {
        return view('livewire.customer.profile')
            ->layout('layouts.customer');
    }
}
