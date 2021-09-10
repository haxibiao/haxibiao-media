<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') 全网高清免费在线观看 - {{ seo_site_name() }}</title>
    <meta name="keywords" content="@yield('keywords')" />
    <meta name="description" content="@yield('description')" />

    {{-- icon --}}
    <link rel="icon" type="image/png" href="{{ small_logo() }}" sizes="60*60">
    <link rel="icon" type="image/png" href="{{ web_logo() }}" sizes="120*120">
    <link rel="apple-touch-icon" href="{{ touch_logo() }}" sizes="160*160">

    <!-- Icons -->
    <link rel="stylesheet" href="https://at.alicdn.com/t/font_2196966_ttq0ufnu2c.css">

    <!-- Styles -->
    <link href="{{ breeze_mix('css/media.css') }}" rel="stylesheet">
    @stack('styles')

    <!-- Scripts -->
    <script type="text/javascript" src="{{ breeze_mix('js/media.js') }}" defer></script>
    @stack('scripts')

</head>

<body>
    @yield('top')
    @include('parts.movie.header')

    <div id="app">
        @yield('content')
        @include('parts.movie.modal.login')
    </div>
    @include('parts.movie.sidebar')

    {{-- 先注入的vue APP user --}}
    @if (Auth::user())
        <script type="text/javascript">
            const user = {
                id: '{{ Auth::user()->id }}',
                token: '{{ Auth::user()->token }}',
                name: '{{ Auth::user()->name }}',
                avatar: '{{ Auth::user()->avatar }}',
            };
            window.user = user
        </script>
    @endif
    @stack('css')
    @stack('js')

    @include('parts.movie.footer')
    @yield('bottom')
    {!! cms_seo_js() !!}
</body>

</html>
