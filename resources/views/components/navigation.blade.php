@php use NyonCode\WireMds\Services\NavigationBuilder; @endphp
{{-- Navigation Component --}}
{{-- Usage: <x-discovery::navigation zone="admin" /> --}}

@props([
    'zone' => 'frontend',
    'class' => '',
    'itemClass' => '',
    'activeClass' => 'bg-gray-100 font-semibold',
    'groupClass' => 'font-medium text-gray-500 uppercase text-xs tracking-wider',
    'iconClass' => 'w-5 h-5 mr-2',
    'showIcons' => true,
    'showBadges' => true,
])

@php
    $navigation = app(NavigationBuilder::class)->forZone($zone);
@endphp

<nav {{ $attributes->merge(['class' => $class]) }}>
    <ul class="space-y-1">
        @foreach($navigation as $item)
            @include('discovery::components.partials.nav-item', [
                'item' => $item,
                'itemClass' => $itemClass,
                'activeClass' => $activeClass,
                'groupClass' => $groupClass,
                'iconClass' => $iconClass,
                'showIcons' => $showIcons,
                'showBadges' => $showBadges,
                'depth' => 0,
            ])
        @endforeach
    </ul>
</nav>
