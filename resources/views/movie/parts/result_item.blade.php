@php
$isSeries = $movie->count_series > 1
@endphp

<div class="search-result_video">
    <a class="result-thumb lazyload-img" href="/movie/{{ $movie->id }}" target="_blank" title="{{ $movie->name }}"
        style="background-image: url({{ $movie->cover_url }});">
        <span class="play-icon hidden-xs">
            <i class="iconfont icon-play-fill1"></i>
        </span>
        <span class="pic-tag pic-tag-top"
            style="background-color: {{ $isSeries ? '#5bb7fe' : '#f66d9b' }};">{{ $movie->score ?? mt_rand(7, 9) . '.' . mt_rand(1, 9) }}</span>
        <span class="pic-text pic-bottom">{{ $isSeries ? '高清' : 'HD' }}</span>
    </a>
    <div class="result-info">
        <div class="infos">
            <h2 class="video_title">
                <a href="/movie/{{ $movie->id }}" target="_blank">
                    <em class="video_name">{{ $movie->name }}</em>
                </a>
                <span class="video_score">{{ $movie->score ?? mt_rand(7, 9) . '.' . mt_rand(1, 9) }}</span>
            </h2>
            <div class="video_info">
                <div class="info_item">
                    <span class="label">导&nbsp;&nbsp;演：</span>
                    <span class="content"><a href="javascript:;">{{ $movie->producer }}</a></span>
                </div>
                <div class="info_item">
                    <span class="label">主&nbsp;&nbsp;演：</span>
                    <span class="content">
                        {{ $movie->actors }}
                        {{-- <a href="javascript:void(0)" target="_blank">沈磊</a>
                        <a href="javascript:void(0)" target="_blank">程玉珠</a>
                        <a href="javascript:void(0)" target="_blank">黄翔宇</a> --}}
                    </span>
                </div>
                <div class="info_item">
                    <span class="label">分&nbsp;&nbsp;类：</span>
                    <span class="content">
                        <a href="javascript:void(0)" target="_blank">{{ $movie->type_name_attr ?? '' }}</a>
                    </span>
                </div>
                <div class="info_item info_item_desc">
                    <span class="label">简&nbsp;&nbsp;介：</span>
                    <span class="desc_text">
                        {{ $movie->introduction }}
                        <a class="desc_more" href="/movie/{{ $movie->id }}" target="_blank">
                            详细<i class="iconfont icon-arrow-right"></i>
                        </a>
                    </span>
                </div>
                <div class="video_operation">
                    <a class="video-btn play" href="/movie/{{ $movie->id }}">
                        <i class="iconfont icon-play-fill"></i>&nbsp;立即播放
                    </a>
{{--                    <a class="video-btn detail" href="/detail/{{ $movie->id }}">--}}
{{--                        查看详情&nbsp;<i class="iconfont icon-arrow-right"></i>--}}
{{--                    </a>--}}
                </div>
            </div>
        </div>
    </div>
</div>
