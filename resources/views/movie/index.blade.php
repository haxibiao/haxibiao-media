@extends('layouts.movie')
@section('title') {{ seo_site_name() }} - {{ cms_seo_title() }} @stop
@section('keywords') {{ cms_seo_keywords() }} @stop
@section('description') {{ cms_seo_description() }} @stop
@section('top')
<div class="header-top-bg"></div>
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
                    <a class="more" href="/movie/{{ $movies[2] }}">更多
                        <i class="iconfont icon-arrow-right"></i>
                    </a>
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
                        <a class="movie-title text-ellipsis" href="/movie/{{ $movie->id }}" title={{ $movie->name }}>
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
