@php use NyonCode\WireMds\Services\BreadcrumbService; @endphp
{{-- Breadcrumbs Component --}}
{{-- Usage: <x-discovery::breadcrumbs :parameters="['id' => $product->name]" /> --}}

@props([
    'parameters' => [],
    'separator' => config('discovery.breadcrumbs.separator', '/'),
    'class' => '',
    'itemClass' => '',
    'activeClass' => 'font-semibold',
    'linkClass' => 'hover:underline',
])

@php
    $breadcrumbs = app(BreadcrumbService::class)->generate($parameters);
@endphp

@if($breadcrumbs->isNotEmpty())
    <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'flex items-center space-x-2 text-sm ' . $class]) }}>
        <ol class="flex items-center space-x-2">
            @foreach($breadcrumbs as $index => $crumb)
                <li class="flex items-center {{ $itemClass }}">
                    @if($index > 0)
                        <span class="mx-2 text-gray-400">{{ $separator }}</span>
                    @endif

                    @if($crumb['active'] || !$crumb['url'])
                        <span class="{{ $crumb['active'] ? $activeClass : '' }}"
                              @if($crumb['active']) aria-current="page" @endif>
                        {{ $crumb['label'] }}
                    </span>
                    @else
                        <a href="{{ $crumb['url'] }}" class="{{ $linkClass }}">
                            {{ $crumb['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
