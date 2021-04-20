@php
$cateogriesMenu = [
    '/' => '首页',
    '/movie/riju' => '日剧',
    '/movie/meiju' => '美剧',
    '/movie/hanju' => '韩剧',
    '/movie/gangju' => '港剧',
    // '/movie/category/8' => '解说',
    // '/collection' => '合集',
];
@endphp

<div id="app-overlay">
    <div class="overlay-top"></div>
    <div class="drawer__wrap drawer__nav">
        <div class="drawer__body">
            <div class="nav-drawer-box">
                @foreach ($cateogriesMenu as $key => $category)
                    @if (isset($cate) && $cate == $category)
                        <a class="link-item active" href={{ $key }}>{{ $category }}</a>
                    @elseif ($key == '/')
                        <a class="link-item active" href={{ $key }}>{{ $category }}</a>
                    @else
                        <a class="link-item" href={{ $key }}>{{ $category }}</a>
                    @endif
                @endforeach
                <span class="pullup">
                    <i class="iconfont icon-arrow-down"></i>
                </span>
            </div>
        </div>
    </div>
</div>
