@php
$cateogries = [
    '/movie/dianshiju' => '电视剧',
    '/movie/dianying' => '电影',
    '/movie/dongman' => '动漫',
    '/movie/zongyi' => '综艺',
    '/movie/lunli' => '伦理',
];
@endphp
<li class="hide-xs" title="展开更多" dropdown-target=".category-menu" dropdown-toggle="hover">
    <a href="/">
        影厅
        <i class="iconfont icon-arrow-down"></i>
    </a>
    <div class="dropdown-box category-menu">
        <ul class="menu-list">
            @include('movie.parts.header_menu_drop',['item_class'=>'menu-item'])
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
<li>
    <a href="{{ config('cms.app_download_page_url', '/app') }}" class="download-app">
        <i class="iconfont icon-mobile">
        </i>下载App
    </a>
</li>
