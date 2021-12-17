<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <meta name="keywords" content="@yield('keywords')" />
    <meta name="description" content="@yield('description')" />
    @pwa
    <link rel="icon" type="image/png" href="{{ small_logo() }}" sizes="60*60">
    <link rel="icon" type="image/png" href="{{ web_logo() }}" sizes="120*120">
    <link rel="apple-touch-icon" href="{{ touch_logo() }}" sizes="160*160">
    <link rel="stylesheet" href="https://at.alicdn.com/t/font_2196966_ttq0ufnu2c.css">
    <link href="{{ media_mix('css/media.css') }}" rel="stylesheet">
    @stack('styles')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest" defer></script>
    <script type="text/javascript" src="{{ media_mix('js/media.js') }}" defer></script>
    @stack('scripts')
</head>

<body>
    @yield('top')
    @include('movie.parts.header')
    <div id="app">
        @yield('content')
        @include('movie.modal.login')
        @stack('bottom')
    </div>
    {{-- 注入的vue全局对象 --}}
    <script type="text/javascript">
        @if (Auth::user())
            window.user = {
            id: '{{ Auth::user()->id }}',
            token: '{{ Auth::user()->token }}',
            name: '{{ Auth::user()->name }}',
            avatar: '{{ Auth::user()->avatar }}',
            };
        @endif
        window.fallback_movie = '{{ env('FALLBACK_MOVIE') }}';
    </script>
    @stack('css')
    @stack('js')
    @include('movie.parts.footer')
    {!! cms_seo_js() !!}
    @yield('bottom')
</body>

</html>
