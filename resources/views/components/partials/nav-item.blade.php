{{-- Navigation Item Partial (Recursive) --}}

@if($item['type'] === 'group')
    {{-- Group Header --}}
    <li class="pt-4 first:pt-0">
        <span class="{{ $groupClass }} block px-3 py-2">
            {{ $item['label'] }}
        </span>
        
        @if(!empty($item['children']))
            <ul class="mt-1 space-y-1 @if($depth > 0) ml-4 @endif">
                @foreach($item['children'] as $child)
                    @include('discovery::components.partials.nav-item', [
                        'item' => $child,
                        'itemClass' => $itemClass,
                        'activeClass' => $activeClass,
                        'groupClass' => $groupClass,
                        'iconClass' => $iconClass,
                        'showIcons' => $showIcons,
                        'showBadges' => $showBadges,
                        'depth' => $depth + 1,
                    ])
                @endforeach
            </ul>
        @endif
    </li>
@else
    {{-- Navigation Item --}}
    <li>
        @if($item['url'] && !$item['has_params'])
            <a 
                href="{{ $item['url'] }}" 
                class="flex items-center px-3 py-2 rounded-md {{ $itemClass }} {{ $item['active'] ? $activeClass : '' }}"
                @if($item['active']) aria-current="page" @endif
            >
                @if($showIcons && $item['icon'])
                    <x-dynamic-component :component="'heroicon-o-' . $item['icon']" class="{{ $iconClass }}" />
                @endif
                
                <span class="flex-1">{{ $item['label'] }}</span>
                
                @if($showBadges && $item['badge'])
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $item['badge_color'] ?? 'bg-gray-200 text-gray-700' }}">
                        {{ $item['badge'] }}
                    </span>
                @endif
            </a>
        @else
            <span class="flex items-center px-3 py-2 rounded-md text-gray-400 {{ $itemClass }}">
                @if($showIcons && $item['icon'])
                    <x-dynamic-component :component="'heroicon-o-' . $item['icon']" class="{{ $iconClass }}" />
                @endif
                
                <span class="flex-1">{{ $item['label'] }}</span>
            </span>
        @endif
    </li>
@endif
