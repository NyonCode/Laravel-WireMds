{{-- Meta Tags Component --}}
{{-- Usage: <x-discovery::meta-tags /> --}}

@php
    use NyonCode\WireMds\Services\MetaRenderer;

    $seo = app(MetaRenderer::class);
@endphp

{!! $seo->render() !!}
