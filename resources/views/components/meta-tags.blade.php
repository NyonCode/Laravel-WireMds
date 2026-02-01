{{-- Meta Tags Component --}}
{{-- Usage: <x-discovery::meta-tags /> --}}

@php
    $seo = app(\App\Support\Discovery\Services\MetaRenderer::class);
@endphp

{!! $seo->render() !!}
