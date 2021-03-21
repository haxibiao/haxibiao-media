@extends('layouts.movie')

@section('title')
    {{ $movie->name }} -
@endsection


@section('content')
    <div class="app-player">
        <div class="container-xl">
            <movie-player :movie-data='{{ $movie }}' qrcode={{ '/app/qrcode' }}
                init-episode={{ 0 }} />
            <span class="movie_loading"></span>
        </div>
    </div>
    <div class="container-xl">
        <div class="sec-main row">
            <div class="main-left col-lg-9">
                <div class="video-info">
                    <div id="media_module" class="clearfix report-wrap-module">
                        <a href="/detail/{{ $movie->id }}" target="_blank" class="video-cover">
                            <img src="{{ $movie->cover_url }}" alt="">
                        </a>
                        <div class="video-right">
                            <a href="/detail/{{ $movie->id }}" target="_blank" title="{{ $movie->name }}"
                                class="video-title">{{ $movie->name }}
                            </a>
                            <div class="video-count text-ellipsis">
                                {{ mt_rand(30, 120) }}.5万播放&nbsp;&nbsp;·&nbsp;&nbsp;{{ mt_rand(1000, 10000) }}人收藏&nbsp;&nbsp;·&nbsp;&nbsp;{{ $movie->comment_count }}评论
                            </div>
                            <div class="pub-wrapper clearfix">
                                <a href="/" target="_blank"
                                    class="home-link">{{ $movie->count_series > 1 ? '电视剧' : '电影' }}</a>
                                <span class="pub-info">{{ $movie->finished ? '已完结' : '更新中' }},
                                    {{ $movie->count_series }}话</span>
                            </div>
                            <a target="_blank" class="video-desc webkit-ellipsis">
                                <span>{!! $movie->introduction !!}</span>
                                <i style="">展开</i>
                            </a>
                            <div class="video-rating">
                                <h4 class="score">{{ $movie->score ?? mt_rand(7, 9) . '.' . mt_rand(1, 9) }}</h4>
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
                        @foreach ($recommend as $relateMovie)
                            <div class="col-xs-6 col-sm-6">
                                @include('parts.movie.recommend_movie_item')
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="video-comment">
                    <comment-module movie-id={{ $movie->id }} count-comment={{ $movie->comment_count }}
                        page-offset={{ 10 }} />
                </div>
            </div>
            <div class="side-right col-lg-3 hide-md">
                <div class="recommend-module">
                    <div class="rc-title">
                        近期热播榜单
                    </div>
                    <div class="rc-list row margin-0">
                        @foreach ($more as $relateMovie)
                            <div class="col-lg-12 col-sm-6 padding-0">
                                @include('parts.movie.recommend_movie_item')
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('parts.modal.report')
@endsection
