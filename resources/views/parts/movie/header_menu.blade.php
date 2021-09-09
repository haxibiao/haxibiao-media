@php
$cateogries = [
    '/movie/riju' => '日剧',
    '/movie/meiju' => '美剧',
    '/movie/hanju' => '韩剧',
    '/movie/gangju' => '港剧',
    // '/movie/category/8' => '解说',
];
@endphp
<li class="hide-xs" title="展开更多" dropdown-target=".category-menu" dropdown-toggle="hover">
    <a href="/">
        主站
        <i class="iconfont icon-arrow-down"></i>
    </a>
    <div class="dropdown-box category-menu">
        <ul class="menu-list">
            @include('parts.movie.header_menu_drop',['item_class'=>'menu-item'])
        </ul>
    </div>
</li>
@foreach ($cateogries as $key => $category)
    @if (isset($cate) && $cate == $category)
        <li class="hide-xs active"><a href={{ $key }}>{{ $category }}</a></li>
    @else
        <li class="hide-xs"><a href={{ $key }}>{{ $category }}</a></li>
    @endif
@endforeach
<li><a href='/app' class="download-app"><i class="iconfont icon-mobile"></i> 下载App</a></li>
<li><a href='https://jx.juhaokantv.com' class="download-app" target="_blank"><i class="iconfont icon-play-fill"></i> 在线解析看剧</a></li>
