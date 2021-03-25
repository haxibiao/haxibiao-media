@php
$movieSection = array_fill(0, 4, '最新电视剧');
$movieList = array_fill(0, 6, 'movieObject');
$movieTitle = array_fill(0, 12, '我和我的家乡');
$videoList = array_fill(0, 4, 'video_list');
@endphp

@extends('layouts.movie')

@section('title') 在线观看,独家高清免费 @stop

@section('keywords') 在线电影,最新电影,免费电影,电影在线观看,内涵电影，{{ seo_site_name() }} @stop

@section('description') {{seo_site_name()}}({{get_domain()}})免费提供热门电影、热播韩剧、高分美剧、最新日剧，及时收录最新、最热、最全的电影大片，高清正版视频免费在线观看 @stop


@section('top')
    <div class="home-top-bg"></div>
@endsection

@section('content')
    <div class="hot-video-box">
        <div class="container-xl">
            <div class="row">
                @include('parts.movie.hot_movies')
            </div>
        </div>
    </div>
    <div class="movies-panel-container">
        <div class="container-xl">
            <div class="row">
                @foreach ($categoryMovies as $cate => $movies)
                    <div class="movies-panel">
                        <div class="panel-head clearfix">
                            <div class="title">
                                {{ $cate }}
                            </div>
                            <a class="more" href="/movie/{{ $movies[2] }}">更多<i class="iconfont icon-arrow-right"></i></a>
                        </div>
                        <div class="movie-list">
                            @foreach ($movies[0] as $movie)
                                <div class="col-lg-2 col-md-3 col-sm-3 col-xs-4">
                                    @include('parts.movie.movie_item')
                                </div>
                            @endforeach
                        </div>
                        <ul class="movie-list_title col-row clearfix">
                            @foreach ($movies[1] as $index => $movie)
                                <li class="col-lg-2 col-md-3 col-xs-6 padding-0">
                                    <a class="movie-title text-ellipsis" href="/movie/{{ $movie->id }}"
                                        title={{ $movie->name }}>
                                        <span class="badge">{{ $index + 1 }}</span>{{ $movie->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="video-list-container">
        <div class="container-xl">
            <div class="row">
                @foreach ($cate_ranks as $title => $data)
                    <div class="col-lg-3 col-sm-6 col-xs-12">
                        @include('parts.movie.home_region_rank',[
                        'title'=>$title,
                        'data'=>$data,
                        ])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection


