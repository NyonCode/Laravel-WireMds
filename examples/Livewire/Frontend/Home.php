<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Support\Discovery\Attributes\Navigation;
use App\Support\Discovery\Attributes\Seo;
use App\Support\Discovery\Attributes\WebRoute;
use Livewire\Component;

/**
 * Frontend Home Page - Example Component
 * 
 * Demonstrates public routes without access restrictions.
 */
#[WebRoute(
    uri: '/',
    name: 'home',
    zone: 'frontend'
)]
#[Navigation(
    label: 'Home',
    icon: 'home',
    sort: 1
)]
#[Seo(
    title: 'Welcome',
    description: 'Welcome to our website',
    sitemap_priority: 1.0,
    sitemap_frequency: 'daily',
    og_type: 'website'
)]
class Home extends Component
{
    public function render()
    {
        return view('livewire.frontend.home')
            ->layout('layouts.app');
    }
}
