@php
$cateogriesMenu = [
    '/movie' => '影厅',
    '/movie/dianshiju' => '电视剧',
    '/movie/dianying' => '电影',

    '/movie/dongman' => '动漫',
    '/movie/zongyi' => '综艺',
    '/movie/lunli' => '伦理',

    '/movie/meiju' => '美剧',
    '/movie/hanju' => '韩剧',
    '/movie/gangju' => '港剧',
    '/movie/riju' => '日剧',

    '/movie/qita' => '其他',
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
