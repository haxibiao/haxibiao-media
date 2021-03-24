@php
    $cateogriesMenu = [
    '/' => '首页',
    '/movie/riju' => '日剧',
    '/movie/meiju' => '美剧',
    '/movie/hanju' => '韩剧',
    //'/movie/gangju' => '港剧',
    // '/movie/category/8' => '解说',
    // '/collection' => '合集',
    ];
@endphp

@foreach ($cateogriesMenu as $key => $category)
    @if (isset($cate) && $cate == $category)
        <li class="{{ $item_class }} active"><a href={{ $key }}>{{ $category }}</a>
        </li>
    @else
        <li class="{{ $item_class }}"><a href={{ $key }}>{{ $category }}</a></li>
    @endif
@endforeach
