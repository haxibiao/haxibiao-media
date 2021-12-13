@extends('layouts.movie')

@section('title')
    {{ seo_site_name() }} -《{{ $movie->name }}》
@endsection
@section('keywords')
    {{ $movie->name }}
@endsection
@section('description')
    {{ $movie->introduction }}
@endsection

@section('content')
    <div class="app-player">
        <div class="container-xl">
            <movie-player :movie-data='{{ $movie }}' qrcode={{ app_qrcode_url() }} init-episode={{ 0 }}
                api-save-progress="/api/movie/save-watch_progress" apk_url="{{config('cms.app_download_apk_url')}}" app_download="{{config('cms.app_download_apk_url')}}" />
            <span class="movie_loading"></span>

            @push('bottom')
                <div style="position:absolute; z-index:2000; top:0; right:20px; background-color:#333">
                    <invite-modal :movie="{{ $movie }}" />
                </div>
            @endpush
        </div>
    </div>
    <div class="container-xl">
        <div class="sec-main row">
            <div class="main-left col-lg-9">
                <div class="video-info">
                    <div id="media_module" class="clearfix report-wrap-module">
                        <div class="video-cover">
                            <img src="{{ $movie->cover_url }}" alt="">
                        </div>
                        <div class="video-right">
                            <div class="video-title">{{ $movie->name }}</div>
                            <div class="video-count text-ellipsis">
                                {{ mt_rand(30, 120) }}.5万播放&nbsp;&nbsp;·&nbsp;&nbsp;{{ mt_rand(1000, 10000) }}人收藏&nbsp;&nbsp;·&nbsp;&nbsp;{{ $movie->comment_count }}评论
                            </div>
                            <div class="pub-wrapper">
                                <a href="/" target="_blank"
                                    class="home-link">{{ $movie->count_series > 1 ? '电视剧' : '电影' }}</a>
                                <span class="pub-info">{{ $movie->finished ? '已完结' : '更新中' }},
                                    {{ $movie->count_series }}话</span>
                                <span class="up-info-wrapper">
                                    <i class="split-line"></i>
                                    <span class="up-info">
                                        <a href="/user/1" target="_blank">
                                            <div class="common-lazy-img">
                                                <img alt="avatar" src="/images/movie/noavatar.png" lazy="loaded">
                                            </div>
                                            <span class="up-name">迷影社</span>
                                        </a>
                                    </span>

                                </span>
                            </div>
                            <a target="_blank" class="video-desc webkit-ellipsis">
                                <span>{!! $movie->introduction !!}</span>
                                <i style="">展开</i>
                            </a>
                            <div class="video-rating">
                                <h4 class="score">{{ $movie->score ?? mt_rand(7, 9) . '.' . mt_rand(1, 9) }}
                                </h4>
                                <p>{{ mt_rand(99000, 990000) }}人评分</p>
                            </div>
                            {{-- @include('parts.movie.video_toolbar') --}}
                        </div>
                    </div>
                </div>

                <div class="recommend-module">
                    <div class="rc-title ">
                        相关视频推荐
                    </div>
                    <div class="rc-list row">
                        @foreach ($recommend ?? [] as $relateMovie)
                            <div class="col-xs-6 col-sm-6">
                                @include('parts.movie.recommend_movie_item')
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="video-comment">
                    <comment-module movie-id="{{ $movie->id }}" count-comment="{{ $movie->comment_count }}"
                        page-offset="{{ 10 }}" />
                </div>
            </div>
            <div class="side-right col-lg-3 hide-md">
                <div class="recommend-module">
                    <div class="rc-title">
                        近期热播榜单
                    </div>
                    <div class="rc-list row margin-0">
                        @foreach ($more ?? [] as $relateMovie)
                            <div class="col-lg-12 col-sm-6 padding-0">
                                @include('parts.movie.recommend_movie_item')
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('parts.movie.modal.report')
@endsection
